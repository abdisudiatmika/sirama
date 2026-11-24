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

/**
 * Lets the admin enter assessor scores for one student from a single Excel
 * file: one row per submitted course, one column per assessor (Asesor 1–3).
 *
 * Each value becomes that assessor's final score for the course (nilai and
 * nilai_akhir alike, as the SK import does), so the recap average is simply
 * the average of the three columns. A blank cell leaves the current score
 * untouched. Rows written this way are marked with diinput_oleh.
 */
class NilaiAdminService
{
    /** Header cell that marks the table; the identity block sits above it. */
    private const PENANDA_TABEL = 'Kode MK';

    private const KOLOM_ASESOR = ['E' => 1, 'F' => 2, 'G' => 3];

    public function __construct(private StatusBerkasService $statusBerkas) {}

    /** Submitted courses in catalog order, with each assessor's current final score. */
    public function baris(Mahasiswa $mahasiswa): Collection
    {
        $mahasiswa->loadMissing(['pengajuanMk.matakuliah', 'pengajuanMk.penilaian', 'penugasanAsesor.asesor.user']);

        return $mahasiswa->pengajuanMk
            ->sortBy([
                fn ($a, $b) => ($a->matakuliah->semester === null) <=> ($b->matakuliah->semester === null),
                fn ($a, $b) => $a->matakuliah->semester <=> $b->matakuliah->semester,
                fn ($a, $b) => $a->matakuliah->id <=> $b->matakuliah->id,
            ])
            ->values()
            ->map(fn (PengajuanMk $p) => (object) [
                'pengajuan' => $p,
                'nilai' => collect(self::KOLOM_ASESOR)->mapWithKeys(function ($urutan) use ($mahasiswa, $p) {
                    $asesorId = $mahasiswa->penugasanAsesor->firstWhere('urutan', $urutan)?->asesor_id;

                    return [$urutan => $asesorId ? $p->penilaian->where('asesor_id', $asesorId)->first()?->nilai_akhir : null];
                }),
            ]);
    }

    public function template(Mahasiswa $mahasiswa): Spreadsheet
    {
        $mahasiswa->loadMissing(['user', 'skema', 'penugasanAsesor.asesor.user']);
        $book = new Spreadsheet;
        $sheet = $book->getActiveSheet()->setTitle('Nilai');

        $sheet->setCellValue('A1', 'TEMPLATE NILAI ASESOR — SIRAMA');
        $sheet->setCellValueExplicit('A2', 'NIM', DataType::TYPE_STRING);
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
        $sheet->getStyle('A7:G7')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F46E5']],
            'alignment' => ['wrapText' => true, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        $baris = $this->baris($mahasiswa);
        foreach ($baris as $i => $b) {
            $r = 8 + $i;
            $sheet->setCellValue("A{$r}", $i + 1);
            $sheet->setCellValueExplicit("B{$r}", $b->pengajuan->matakuliah->kode, DataType::TYPE_STRING);
            $sheet->setCellValue("C{$r}", $b->pengajuan->matakuliah->nama);
            $sheet->setCellValue("D{$r}", (int) $b->pengajuan->matakuliah->sks);
            foreach (self::KOLOM_ASESOR as $kolom => $urutan) {
                if ($b->nilai[$urutan] !== null) {
                    $sheet->setCellValue("{$kolom}{$r}", (float) $b->nilai[$urutan]);
                }
                if (! $mahasiswa->penugasanAsesor->firstWhere('urutan', $urutan)) {
                    $sheet->getStyle("{$kolom}{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E2E8F0');
                }
            }
        }

        $akhir = 7 + max(1, $baris->count());
        $sheet->getStyle("A7:G{$akhir}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('CBD5E1');
        $sheet->getStyle("E8:G{$akhir}")->getNumberFormat()->setFormatCode('0.00');
        foreach (['A' => 5, 'B' => 14, 'C' => 42, 'D' => 6, 'E' => 26, 'F' => 26, 'G' => 26] as $kolom => $lebar) {
            $sheet->getColumnDimension($kolom)->setWidth($lebar);
        }
        $sheet->getRowDimension(7)->setRowHeight(32);
        $sheet->freezePane('A8');

        return $book;
    }

    public function namaFile(Mahasiswa $mahasiswa): string
    {
        return 'Nilai-Asesor-'.str($mahasiswa->user->name)->slug().'-'.$mahasiswa->nim.'.xlsx';
    }

    /**
     * Reads a filled template and saves every score in it, or nothing at all
     * when any row is wrong (the errors are returned so the admin can fix the
     * file and upload it again).
     *
     * @return array{tersimpan: int, berubah: int, galat: list<string>}
     */
    public function impor(Mahasiswa $mahasiswa, string $path, int $userId): array
    {
        $mahasiswa->loadMissing(['pengajuanMk.matakuliah', 'pengajuanMk.mkAsal', 'pengajuanMk.bukti', 'pengajuanMk.penilaian', 'penugasanAsesor']);

        try {
            $sheet = IOFactory::load($path)->getSheet(0);
        } catch (\Throwable) {
            return ['tersimpan' => 0, 'berubah' => 0, 'galat' => ['File tidak bisa dibaca. Gunakan template Excel (.xlsx) yang diunduh dari sistem.']];
        }

        $nimFile = trim((string) $sheet->getCell('B2')->getValue());
        if ($nimFile !== $mahasiswa->nim) {
            return ['tersimpan' => 0, 'berubah' => 0, 'galat' => ["Template ini untuk NIM {$nimFile}, bukan {$mahasiswa->nim}. Unduh template dari panel mahasiswa yang benar."]];
        }

        $mulai = null;
        for ($r = 1; $r <= min(50, $sheet->getHighestRow()); $r++) {
            if (trim((string) $sheet->getCell("B{$r}")->getValue()) === self::PENANDA_TABEL) {
                $mulai = $r + 1;
                break;
            }
        }
        if (! $mulai) {
            return ['tersimpan' => 0, 'berubah' => 0, 'galat' => ['Kolom "Kode MK" tidak ditemukan. Gunakan template yang diunduh dari sistem.']];
        }

        $pengajuan = $mahasiswa->pengajuanMk->keyBy(fn ($p) => $p->matakuliah->kode);
        $asesor = $mahasiswa->penugasanAsesor->pluck('asesor_id', 'urutan');
        $galat = [];
        $tulis = [];

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

            foreach (self::KOLOM_ASESOR as $kolom => $urutan) {
                $mentah = $sheet->getCell("{$kolom}{$r}")->getCalculatedValue();
                if ($mentah === null || trim((string) $mentah) === '' || trim((string) $mentah) === '-') {
                    continue;
                }
                $teks = str_replace(',', '.', trim((string) $mentah));
                if (! is_numeric($teks) || (float) $teks < 0 || (float) $teks > 100) {
                    $galat[] = "Baris {$r} ({$kode}), Asesor {$urutan}: nilai \"{$mentah}\" harus angka 0–100.";

                    continue;
                }
                if (! $asesor->has($urutan)) {
                    $galat[] = "Baris {$r} ({$kode}): Asesor {$urutan} belum ditugaskan, nilainya tidak bisa disimpan.";

                    continue;
                }
                $tulis[] = [$p, $asesor[$urutan], round((float) $teks, 2)];
            }
        }

        if ($galat) {
            return ['tersimpan' => 0, 'berubah' => 0, 'galat' => $galat];
        }

        $berubah = 0;
        DB::transaction(function () use ($tulis, $userId, &$berubah) {
            foreach ($tulis as [$p, $asesorId, $nilai]) {
                $berubah += $this->simpan($p, $asesorId, $nilai, $userId) ? 1 : 0;
            }
        });

        $this->statusBerkas->perbarui($mahasiswa->fresh());

        return ['tersimpan' => count($tulis), 'berubah' => $berubah, 'galat' => []];
    }

    /**
     * Writes one assessor's final score on every assessment row the course
     * needs (formal and/or informal, from its evidence), so the course counts
     * as finished for that assessor. Returns whether anything changed.
     */
    private function simpan(PengajuanMk $p, int $asesorId, float $nilai, int $userId): bool
    {
        $ada = Penilaian::where('pengajuan_mk_id', $p->id)->where('asesor_id', $asesorId)->get();
        $jenis = $p->jenisPenilaianTersedia()
            ?: ($ada->pluck('jenis')->unique()->values()->all()
                ?: [$p->mkAsal->isNotEmpty() ? 'formal' : 'informal']);

        $sebelum = $ada->pluck('nilai_akhir')->map(fn ($v) => (float) $v)->unique()->values()->all();
        $barisBaru = collect($jenis)->diff($ada->pluck('jenis'))->isNotEmpty();

        foreach ($jenis as $j) {
            Penilaian::updateOrCreate(
                ['pengajuan_mk_id' => $p->id, 'asesor_id' => $asesorId, 'jenis' => $j],
                ['nilai' => $nilai, 'nilai_akhir' => $nilai, 'diinput_oleh' => $userId],
            );
        }

        // Rows of another jenis (from older evidence) keep the same final score, so the average stays one value per assessor.
        Penilaian::where('pengajuan_mk_id', $p->id)->where('asesor_id', $asesorId)->whereNotIn('jenis', $jenis)
            ->update(['nilai' => $nilai, 'nilai_akhir' => $nilai, 'diinput_oleh' => $userId]);

        return $barisBaru || $sebelum !== [$nilai];
    }
}
