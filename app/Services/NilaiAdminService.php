<?php

namespace App\Services;

use App\Models\Mahasiswa;
use App\Models\PengajuanMk;
use App\Models\Penilaian;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Lets the admin enter assessor scores from Excel, either for one student
 * (from the student panel) or for many at once (the Input Nilai menu).
 *
 * Each value becomes that assessor's final score for the course (nilai and
 * nilai_akhir alike, as the SK import does), so the recap average is simply
 * the average of the three assessor columns. A blank cell leaves the current
 * score untouched, a file with any bad row saves nothing, and rows written
 * this way are marked with diinput_oleh.
 */
class NilaiAdminService
{
    /** Per-student template: identity block on top, table header marked by this cell in column B. */
    private const PENANDA_TABEL = 'Kode MK';

    private const KOLOM_ASESOR = ['E' => 1, 'F' => 2, 'G' => 3];

    /** Bulk template: NIM in B, course code in D, assessor scores in F–H, assessor names in I–K. */
    private const KOLOM_ASESOR_MASSAL = ['F' => 1, 'G' => 2, 'H' => 3];

    private const RELASI = ['user', 'skema', 'pengajuanMk.matakuliah', 'pengajuanMk.mkAsal', 'pengajuanMk.bukti', 'pengajuanMk.penilaian', 'penugasanAsesor.asesor.user'];

    public function __construct(private StatusBerkasService $statusBerkas) {}

    // ---------------------------------------------------------------- per mahasiswa

    /** Submitted courses in catalog order, with each assessor's current final score keyed by assessor slot. */
    public function baris(Mahasiswa $mahasiswa): Collection
    {
        $mahasiswa->loadMissing(self::RELASI);

        return $this->urutkan($mahasiswa->pengajuanMk)->map(fn (PengajuanMk $p) => (object) [
            'pengajuan' => $p,
            'nilai' => collect([1, 2, 3])->mapWithKeys(fn ($urutan) => [$urutan => $this->nilaiAsesor($mahasiswa, $p, $urutan)]),
        ]);
    }

    public function template(Mahasiswa $mahasiswa): Spreadsheet
    {
        $mahasiswa->loadMissing(self::RELASI);
        $book = new Spreadsheet;
        $sheet = $book->getActiveSheet()->setTitle('Nilai');

        $sheet->setCellValue('A1', 'TEMPLATE NILAI ASESOR — SIRAMA');
        $sheet->setCellValue('A2', 'NIM');
        $sheet->setCellValueExplicit('B2', $mahasiswa->nim, DataType::TYPE_STRING);
        $sheet->setCellValue('A3', 'Nama');
        $sheet->setCellValue('B3', $mahasiswa->user->name);
        $sheet->setCellValue('A4', 'Skema');
        $sheet->setCellValue('B4', $mahasiswa->skema?->nama ?? '-');
        $sheet->setCellValue('A5', 'Isi nilai 0–100 (boleh dua desimal) pada kolom asesor. Sel kosong tidak mengubah nilai yang sudah ada. Jangan mengubah NIM dan Kode MK.');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $sheet->getStyle('A2:A4')->getFont()->setBold(true);
        $sheet->getStyle('A5')->getFont()->setItalic(true)->getColor()->setRGB('64748B');

        $kepala = ['No', self::PENANDA_TABEL, 'Nama MK', 'SKS'];
        foreach (self::KOLOM_ASESOR as $urutan) {
            $penugasan = $mahasiswa->penugasanAsesor->firstWhere('urutan', $urutan);
            $kepala[] = "Asesor {$urutan}".($penugasan ? ' — '.$penugasan->asesor->user->name : ' (belum ditugaskan)');
        }
        $sheet->fromArray($kepala, null, 'A7');
        $this->gayaKepala($sheet, 'A7:G7');

        $baris = $this->baris($mahasiswa);
        foreach ($baris as $i => $b) {
            $r = 8 + $i;
            $sheet->setCellValue("A{$r}", $i + 1);
            $sheet->setCellValueExplicit("B{$r}", $b->pengajuan->matakuliah->kode, DataType::TYPE_STRING);
            $sheet->setCellValue("C{$r}", $b->pengajuan->matakuliah->nama);
            $sheet->setCellValue("D{$r}", (int) $b->pengajuan->matakuliah->sks);
            foreach (self::KOLOM_ASESOR as $kolom => $urutan) {
                $this->isiNilai($sheet, "{$kolom}{$r}", $b->nilai[$urutan], (bool) $mahasiswa->penugasanAsesor->firstWhere('urutan', $urutan));
            }
        }

        $akhir = 7 + max(1, $baris->count());
        $this->gayaTabel($sheet, "A7:G{$akhir}", "E8:G{$akhir}");
        $this->lebarKolom($sheet, ['A' => 5, 'B' => 14, 'C' => 42, 'D' => 6, 'E' => 26, 'F' => 26, 'G' => 26]);
        $sheet->freezePane('A8');

        return $book;
    }

    public function namaFile(Mahasiswa $mahasiswa): string
    {
        return 'Nilai-Asesor-'.str($mahasiswa->user->name)->slug().'-'.$mahasiswa->nim.'.xlsx';
    }

    /**
     * Reads a filled per-student template and saves it straight away.
     *
     * @return array{tersimpan: int, berubah: int, galat: list<string>}
     */
    public function impor(Mahasiswa $mahasiswa, string $path, int $userId): array
    {
        $mahasiswa->loadMissing(self::RELASI);

        $sheet = $this->buka($path);
        if (is_string($sheet)) {
            return $this->gagal($sheet);
        }

        $nimFile = trim((string) $sheet->getCell('B2')->getValue());
        if ($nimFile !== $mahasiswa->nim) {
            return $this->gagal("Template ini untuk NIM {$nimFile}, bukan {$mahasiswa->nim}. Unduh template dari panel mahasiswa yang benar.");
        }

        $mulai = $this->barisKepala($sheet, ['B' => self::PENANDA_TABEL]);
        if (! $mulai) {
            return $this->gagal('Kolom "Kode MK" tidak ditemukan. Gunakan template yang diunduh dari sistem.');
        }

        $pengajuan = $mahasiswa->pengajuanMk->keyBy(fn ($p) => $p->matakuliah->kode);
        $galat = [];
        $rencana = [];

        for ($r = $mulai; $r <= $sheet->getHighestRow(); $r++) {
            $kode = trim((string) $sheet->getCell("B{$r}")->getValue());
            if ($kode === '') {
                continue;
            }
            $p = $pengajuan->get($kode);
            if (! $p) {
                $galat[] = "Baris {$r}: kode MK {$kode} tidak termasuk mata kuliah yang diajukan mahasiswa ini.";

                continue;
            }
            $this->bacaBaris($sheet, $r, self::KOLOM_ASESOR, $mahasiswa, $p, "Baris {$r} ({$kode})", $rencana, $galat);
        }

        if ($galat) {
            return $this->gagal($galat);
        }

        return ['tersimpan' => count($rencana), 'berubah' => $this->simpanRencana($rencana, $userId), 'galat' => []];
    }

    // ---------------------------------------------------------------- massal

    /** Students of a prodi that have submitted courses, narrowed by the menu's filters. */
    public function mahasiswaMassal(int $prodiId, ?int $skemaId = null, ?int $angkatan = null, ?string $status = null): Collection
    {
        return Mahasiswa::where('prodi_id', $prodiId)
            ->when($skemaId, fn ($q) => $q->where('skema_id', $skemaId))
            ->when($angkatan, fn ($q) => $q->where('tahun_masuk', $angkatan))
            ->when($status, fn ($q) => $q->where('status_berkas', $status))
            ->whereHas('pengajuanMk')
            ->with(self::RELASI)
            ->orderBy('nim')
            ->get();
    }

    /** One row per student × submitted course; scores already in the system are filled in. */
    public function templateMassal(Collection $mahasiswa, string $keterangan): Spreadsheet
    {
        $book = new Spreadsheet;
        $sheet = $book->getActiveSheet()->setTitle('Nilai');

        $sheet->setCellValue('A1', 'TEMPLATE NILAI ASESOR (MASSAL) — SIRAMA');
        $sheet->setCellValue('A2', $keterangan);
        $sheet->setCellValue('A3', 'Isi nilai 0–100 (boleh dua desimal) pada kolom Asesor 1–3. Sel kosong tidak mengubah nilai yang sudah ada. Jangan mengubah NIM dan Kode MK; baris boleh difilter, diurutkan, atau dihapus.');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $sheet->getStyle('A3')->getFont()->setItalic(true)->getColor()->setRGB('64748B');

        $sheet->fromArray(['No', 'NIM', 'Nama Mahasiswa', 'Kode MK', 'Nama MK', 'Asesor 1', 'Asesor 2', 'Asesor 3', 'Nama Asesor 1', 'Nama Asesor 2', 'Nama Asesor 3'], null, 'A5');
        $this->gayaKepala($sheet, 'A5:K5');

        $r = 6;
        foreach ($mahasiswa as $m) {
            $nama = collect([1, 2, 3])->map(fn ($u) => $m->penugasanAsesor->firstWhere('urutan', $u)?->asesor->user->name ?? '(belum ditugaskan)');
            foreach ($this->urutkan($m->pengajuanMk) as $p) {
                $sheet->setCellValue("A{$r}", $r - 5);
                $sheet->setCellValueExplicit("B{$r}", $m->nim, DataType::TYPE_STRING);
                $sheet->setCellValue("C{$r}", $m->user->name);
                $sheet->setCellValueExplicit("D{$r}", $p->matakuliah->kode, DataType::TYPE_STRING);
                $sheet->setCellValue("E{$r}", $p->matakuliah->nama);
                foreach (self::KOLOM_ASESOR_MASSAL as $kolom => $urutan) {
                    $this->isiNilai($sheet, "{$kolom}{$r}", $this->nilaiAsesor($m, $p, $urutan), (bool) $m->penugasanAsesor->firstWhere('urutan', $urutan));
                }
                $sheet->fromArray($nama->all(), null, "I{$r}");
                $r++;
            }
        }

        $akhir = max(6, $r - 1);
        $this->gayaTabel($sheet, "A5:K{$akhir}", "F6:H{$akhir}");
        $sheet->getStyle("I6:K{$akhir}")->getFont()->getColor()->setRGB('64748B');
        $this->lebarKolom($sheet, ['A' => 6, 'B' => 16, 'C' => 30, 'D' => 13, 'E' => 36, 'F' => 10, 'G' => 10, 'H' => 10, 'I' => 30, 'J' => 30, 'K' => 30]);
        $sheet->setAutoFilter("A5:K{$akhir}");
        $sheet->freezePane('F6');

        return $book;
    }

    /**
     * Checks a filled bulk template without saving anything.
     *
     * @return array{rencana: list<array{0: PengajuanMk, 1: int, 2: float, 3: Mahasiswa}>, galat: list<string>, ringkasan: array{nilai: int, berubah: int, mahasiswa: list<array{nim: string, nama: string, berubah: int}>}}
     */
    public function periksaMassal(int $prodiId, string $path): array
    {
        $kosong = ['rencana' => [], 'galat' => [], 'ringkasan' => ['nilai' => 0, 'berubah' => 0, 'mahasiswa' => []]];

        $sheet = $this->buka($path);
        if (is_string($sheet)) {
            return ['galat' => [$sheet]] + $kosong;
        }
        $mulai = $this->barisKepala($sheet, ['B' => 'NIM', 'D' => 'Kode MK']);
        if (! $mulai) {
            return ['galat' => ['Kolom "NIM" dan "Kode MK" tidak ditemukan. Gunakan template massal yang diunduh dari menu Input Nilai.']] + $kosong;
        }

        $nimDiFile = [];
        for ($r = $mulai; $r <= $sheet->getHighestRow(); $r++) {
            $nim = trim((string) $sheet->getCell("B{$r}")->getValue());
            if ($nim !== '') {
                $nimDiFile[$nim] = true;
            }
        }
        $mahasiswa = Mahasiswa::where('prodi_id', $prodiId)->whereIn('nim', array_keys($nimDiFile))->with(self::RELASI)->get()->keyBy('nim');

        $galat = [];
        $rencana = [];
        for ($r = $mulai; $r <= $sheet->getHighestRow(); $r++) {
            $nim = trim((string) $sheet->getCell("B{$r}")->getValue());
            $kode = trim((string) $sheet->getCell("D{$r}")->getValue());
            if ($nim === '' && $kode === '') {
                continue;
            }
            $m = $mahasiswa->get($nim);
            if (! $m) {
                $galat[] = "Baris {$r}: NIM {$nim} tidak ditemukan di prodi ini.";

                continue;
            }
            $p = $m->pengajuanMk->first(fn ($x) => $x->matakuliah->kode === $kode);
            if (! $p) {
                $galat[] = "Baris {$r}: kode MK {$kode} tidak termasuk mata kuliah yang diajukan {$m->user->name}.";

                continue;
            }
            $this->bacaBaris($sheet, $r, self::KOLOM_ASESOR_MASSAL, $m, $p, "Baris {$r} ({$nim}, {$kode})", $rencana, $galat);
        }

        $perMahasiswa = [];
        $berubah = 0;
        foreach ($rencana as [$p, $asesorId, $nilai, $m]) {
            if ($this->perluDitulis($p, $asesorId, $nilai)) {
                $berubah++;
                $perMahasiswa[$m->nim] ??= ['nim' => $m->nim, 'nama' => $m->user->name, 'berubah' => 0];
                $perMahasiswa[$m->nim]['berubah']++;
            }
        }

        return [
            'rencana' => $rencana,
            'galat' => $galat,
            'ringkasan' => ['nilai' => count($rencana), 'berubah' => $berubah, 'mahasiswa' => array_values($perMahasiswa)],
        ];
    }

    /**
     * Saves a checked plan: only scores that differ are written, then every
     * affected student's file status is refreshed. Returns how many changed.
     *
     * @param  list<array{0: PengajuanMk, 1: int, 2: float, 3?: Mahasiswa}>  $rencana
     */
    public function simpanRencana(array $rencana, int $userId): int
    {
        @set_time_limit(300);
        $berubah = 0;
        $mahasiswaIds = [];

        DB::transaction(function () use ($rencana, $userId, &$berubah, &$mahasiswaIds) {
            foreach ($rencana as [$p, $asesorId, $nilai]) {
                if (! $this->perluDitulis($p, $asesorId, $nilai)) {
                    continue;
                }
                $this->tulis($p, $asesorId, $nilai, $userId);
                $berubah++;
                $mahasiswaIds[$p->mahasiswa_id] = true;
            }
        });

        foreach (Mahasiswa::whereIn('id', array_keys($mahasiswaIds))->get() as $m) {
            $this->statusBerkas->perbarui($m);
        }

        return $berubah;
    }

    // ---------------------------------------------------------------- bantuan

    /** Reads the three assessor cells of one row into the plan, or explains what is wrong. */
    private function bacaBaris(Worksheet $sheet, int $r, array $kolomAsesor, Mahasiswa $m, PengajuanMk $p, string $label, array &$rencana, array &$galat): void
    {
        foreach ($kolomAsesor as $kolom => $urutan) {
            $mentah = $sheet->getCell("{$kolom}{$r}")->getCalculatedValue();
            $teks = trim((string) $mentah);
            if ($teks === '' || $teks === '-') {
                continue;
            }
            $teks = str_replace(',', '.', $teks);
            if (! is_numeric($teks) || (float) $teks < 0 || (float) $teks > 100) {
                $galat[] = "{$label}, Asesor {$urutan}: nilai \"{$mentah}\" harus angka 0–100.";

                continue;
            }
            $asesorId = $m->penugasanAsesor->firstWhere('urutan', $urutan)?->asesor_id;
            if (! $asesorId) {
                $galat[] = "{$label}: Asesor {$urutan} belum ditugaskan, nilainya tidak bisa disimpan.";

                continue;
            }
            $rencana[] = [$p, $asesorId, round((float) $teks, 2), $m];
        }
    }

    /** Formal and/or informal, from the course's evidence; otherwise what already exists, else by prior-course data. */
    private function jenisUntuk(PengajuanMk $p, Collection $ada): array
    {
        return $p->jenisPenilaianTersedia()
            ?: ($ada->pluck('jenis')->unique()->values()->all()
                ?: [$p->mkAsal->isNotEmpty() ? 'formal' : 'informal']);
    }

    /** True when the assessor lacks a needed row or any of their rows holds another final score. */
    private function perluDitulis(PengajuanMk $p, int $asesorId, float $nilai): bool
    {
        $ada = $p->penilaian->where('asesor_id', $asesorId);

        return collect($this->jenisUntuk($p, $ada))->diff($ada->pluck('jenis'))->isNotEmpty()
            || $ada->contains(fn ($x) => round((float) $x->nilai_akhir, 2) !== $nilai || round((float) $x->nilai, 2) !== $nilai);
    }

    private function tulis(PengajuanMk $p, int $asesorId, float $nilai, int $userId): void
    {
        $ada = Penilaian::where('pengajuan_mk_id', $p->id)->where('asesor_id', $asesorId)->get();
        $jenis = $this->jenisUntuk($p, $ada);

        foreach ($jenis as $j) {
            Penilaian::updateOrCreate(
                ['pengajuan_mk_id' => $p->id, 'asesor_id' => $asesorId, 'jenis' => $j],
                ['nilai' => $nilai, 'nilai_akhir' => $nilai, 'diinput_oleh' => $userId],
            );
        }

        // Rows of another jenis (from older evidence) keep the same final score, so the average stays one value per assessor.
        Penilaian::where('pengajuan_mk_id', $p->id)->where('asesor_id', $asesorId)->whereNotIn('jenis', $jenis)
            ->update(['nilai' => $nilai, 'nilai_akhir' => $nilai, 'diinput_oleh' => $userId]);
    }

    private function nilaiAsesor(Mahasiswa $m, PengajuanMk $p, int $urutan): ?float
    {
        $asesorId = $m->penugasanAsesor->firstWhere('urutan', $urutan)?->asesor_id;
        $nilai = $asesorId ? $p->penilaian->where('asesor_id', $asesorId)->first()?->nilai_akhir : null;

        return $nilai === null ? null : (float) $nilai;
    }

    /** Official catalog order (imported courses have no semester; ids follow the catalog). */
    private function urutkan(Collection $pengajuan): Collection
    {
        return $pengajuan->sortBy([
            fn ($a, $b) => ($a->matakuliah->semester === null) <=> ($b->matakuliah->semester === null),
            fn ($a, $b) => $a->matakuliah->semester <=> $b->matakuliah->semester,
            fn ($a, $b) => $a->matakuliah->id <=> $b->matakuliah->id,
        ])->values();
    }

    private function buka(string $path): Worksheet|string
    {
        try {
            return IOFactory::load($path)->getSheet(0);
        } catch (\Throwable) {
            return 'File tidak bisa dibaca. Gunakan template Excel (.xlsx) yang diunduh dari sistem.';
        }
    }

    /** First data row under the header whose cells match $penanda (column => text), within the first 50 rows. */
    private function barisKepala(Worksheet $sheet, array $penanda): ?int
    {
        for ($r = 1; $r <= min(50, $sheet->getHighestRow()); $r++) {
            if (collect($penanda)->every(fn ($teks, $kolom) => trim((string) $sheet->getCell("{$kolom}{$r}")->getValue()) === $teks)) {
                return $r + 1;
            }
        }

        return null;
    }

    private function gagal(string|array $galat): array
    {
        return ['tersimpan' => 0, 'berubah' => 0, 'galat' => (array) $galat];
    }

    private function isiNilai(Worksheet $sheet, string $sel, ?float $nilai, bool $adaAsesor): void
    {
        if ($nilai !== null) {
            $sheet->setCellValue($sel, $nilai);
        }
        if (! $adaAsesor) {
            $sheet->getStyle($sel)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E2E8F0');
        }
    }

    private function gayaKepala(Worksheet $sheet, string $rentang): void
    {
        $sheet->getStyle($rentang)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F46E5']],
            'alignment' => ['wrapText' => true, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension((int) preg_replace('/\D/', '', explode(':', $rentang)[0]))->setRowHeight(32);
    }

    private function gayaTabel(Worksheet $sheet, string $rentang, string $rentangNilai): void
    {
        $sheet->getStyle($rentang)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('CBD5E1');
        $sheet->getStyle($rentangNilai)->getNumberFormat()->setFormatCode('0.00');
    }

    private function lebarKolom(Worksheet $sheet, array $lebar): void
    {
        foreach ($lebar as $kolom => $l) {
            $sheet->getColumnDimension($kolom)->setWidth($l);
        }
    }
}
