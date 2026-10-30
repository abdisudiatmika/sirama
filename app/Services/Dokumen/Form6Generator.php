<?php

namespace App\Services\Dokumen;

use App\Models\Mahasiswa;
use App\Models\PengajuanMk;
use App\Services\RekapitulasiService;
use Illuminate\Support\Collection;
use PhpOffice\PhpWord\Element\Cell;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\TblWidth;
use PhpOffice\PhpWord\Style\Table;

/**
 * Asesmen Transfer SKS dari Pendidikan Formal: the student's prior courses
 * (mk_asal) next to the target course, with that assessor's formal result.
 * One variant per assessor, like Form 5.
 */
class Form6Generator extends FormulirWord
{
    use VarianPerAsesor;

    private const KOLOM = [
        'no' => 500, 'kode_asal' => 1100, 'nama_asal' => 1800, 'cp_asal' => 3000,
        'nama_tujuan' => 1800, 'cp_tujuan' => 3000, 'kesenjangan' => 1300, 'hasil' => 700, 'catatan' => 1300,
    ];

    private const TEKS = ['size' => 9];

    public function __construct(private RekapitulasiService $rekap) {}

    public function nomor(): int
    {
        return 6;
    }

    protected function lanskap(): bool
    {
        return true;
    }

    protected function isi(Section $section, Mahasiswa $mahasiswa): void
    {
        $mahasiswa->loadMissing(['user', 'prodi', 'biodata', 'pendidikanTerakhir']);
        $penugasan = $this->penugasanVarian($mahasiswa);
        $b = $mahasiswa->biodata;

        $section->addText('ASESMEN TRANSFER SKS DARI PENDIDIKAN FORMAL', ['bold' => true, 'size' => 13], ['alignment' => Jc::CENTER, 'spaceAfter' => 240]);

        $identitas = $section->addTable(['layout' => Table::LAYOUT_FIXED, 'width' => self::LEBAR_HALAMAN_LANSKAP, 'unit' => TblWidth::TWIP, 'borderSize' => 0, 'borderColor' => 'FFFFFF', 'cellMargin' => 20]);
        foreach ([
            ['Nama', $b->nama_lengkap ?? $mahasiswa->user->name],
            ['Alamat', $b?->alamat_rumah],
            ['No Hp', $b?->telp_rumah_hp],
            ['E-mail', $b->email ?? $mahasiswa->user->email],
            ['Jenjang Pendidikan Sebelumnya', $this->rekap->jenjangSebelumnya($mahasiswa)],
            ['Program Studi', 'KELAS RPL '.mb_strtoupper($mahasiswa->prodi->nama)],
            ['Level KKNI', null],
            ['Asesor', $penugasan?->asesor?->user?->name],
        ] as [$label, $nilai]) {
            $identitas->addRow();
            $identitas->addCell(3200)->addText($label, [], ['spaceAfter' => 0]);
            $identitas->addCell(self::LEBAR_HALAMAN_LANSKAP - 3200)->addText(': '.($nilai ?? ''), [], ['spaceAfter' => 0]);
        }

        $section->addTextBreak();
        $this->tabelTransfer($section, $this->pengajuan($mahasiswa), $penugasan?->asesor_id);
        $this->blokTandaTangan($section, $mahasiswa);
    }

    /**
     * Courses with formal evidence, prior-course data or a formal assessment —
     * i.e. the ones the formal transfer route applies to.
     *
     * @return Collection<int, PengajuanMk>
     */
    private function pengajuan(Mahasiswa $mahasiswa): Collection
    {
        return $mahasiswa->pengajuanMk()
            ->join('matakuliah', 'matakuliah.id', '=', 'pengajuan_mk.matakuliah_id')
            ->orderByRaw('matakuliah.semester is null')->orderBy('matakuliah.semester')->orderBy('matakuliah.id')
            ->select('pengajuan_mk.*')
            ->with([
                'matakuliah.cpmk' => fn ($q) => $q->orderBy('urutan')->orderBy('id'),
                'mkAsal',
                'bukti',
                'penilaian' => fn ($q) => $q->where('jenis', 'formal'),
            ])
            ->get()
            ->filter(fn (PengajuanMk $p) => $p->mkAsal->isNotEmpty()
                || $p->penilaian->isNotEmpty()
                || $p->bukti->contains('kategori', 'formal'))
            ->values();
    }

    private function tabelTransfer(Section $section, Collection $pengajuan, ?int $asesorId): void
    {
        $k = self::KOLOM;
        $tabel = $section->addTable([
            'layout' => Table::LAYOUT_FIXED,
            'width' => self::LEBAR_HALAMAN_LANSKAP,
            'unit' => TblWidth::TWIP,
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 50,
        ]);

        $abu = ['bgColor' => 'D9D9D9', 'valign' => 'center'];
        $kepala = ['bold' => true, 'size' => 9];
        $tengah = ['alignment' => Jc::CENTER, 'spaceAfter' => 0];

        $tabel->addRow(null, ['tblHeader' => true]);
        $tabel->addCell($k['no'], $abu + ['vMerge' => 'restart'])->addText('No', $kepala, $tengah);
        $tabel->addCell($k['kode_asal'] + $k['nama_asal'] + $k['cp_asal'], $abu + ['gridSpan' => 3])->addText('Pemohon', $kepala, $tengah);
        $tabel->addCell($k['nama_tujuan'] + $k['cp_tujuan'], $abu + ['gridSpan' => 2])->addText('Prodi yang dituju', $kepala, $tengah);
        $tabel->addCell($k['kesenjangan'], $abu + ['vMerge' => 'restart'])->addText('Kesenjangan', $kepala, $tengah);
        $tabel->addCell($k['hasil'], $abu + ['vMerge' => 'restart'])->addText('Hasil', $kepala, $tengah);
        $tabel->addCell($k['catatan'], $abu + ['vMerge' => 'restart'])->addText('Catatan Asesor', $kepala, $tengah);

        $tabel->addRow(null, ['tblHeader' => true]);
        $tabel->addCell($k['no'], $abu + ['vMerge' => 'continue']);
        foreach (['kode_asal' => 'Kode MK', 'nama_asal' => 'Nama Mata kuliah', 'cp_asal' => 'Capaian Pembelajaran Matakuliah', 'nama_tujuan' => 'Nama Matakuliah', 'cp_tujuan' => 'Capaian Pembelajaran Matakuliah'] as $kunci => $judul) {
            $tabel->addCell($k[$kunci], $abu)->addText($judul, $kepala, $tengah);
        }
        foreach (['kesenjangan', 'hasil', 'catatan'] as $kunci) {
            $tabel->addCell($k[$kunci], $abu + ['vMerge' => 'continue']);
        }

        if ($pengajuan->isEmpty()) {
            $tabel->addRow();
            $tabel->addCell(self::LEBAR_HALAMAN_LANSKAP, ['gridSpan' => 9])->addText('Tidak ada mata kuliah yang diajukan melalui jalur pendidikan formal.', ['italic' => true, 'size' => 9], $tengah);

            return;
        }

        foreach ($pengajuan as $i => $p) {
            $nilai = $asesorId ? $p->penilaian->firstWhere('asesor_id', $asesorId) : null;
            // One row per prior course so its code, name and CP line up; the target-side
            // columns are merged down across them.
            $daftarAsal = $p->mkAsal->isEmpty() ? collect([null]) : $p->mkAsal->values();

            foreach ($daftarAsal as $j => $asal) {
                $gabung = ['vMerge' => $j === 0 ? 'restart' : 'continue'];
                $tabel->addRow(null, ['cantSplit' => true]);

                $sel = $tabel->addCell($k['no'], $gabung);
                if ($j === 0) {
                    $sel->addText((string) ($i + 1), self::TEKS, $tengah);
                }

                $tabel->addCell($k['kode_asal'])->addText((string) $asal?->kode_asal, self::TEKS, ['spaceAfter' => 0]);
                $tabel->addCell($k['nama_asal'])->addText((string) $asal?->nama_mk_asal, self::TEKS, ['spaceAfter' => 0]);
                $this->daftar($tabel->addCell($k['cp_asal']), collect(preg_split('/\R/', (string) $asal?->cpmk_asal)));

                $selTujuan = [
                    'nama_tujuan' => $tabel->addCell($k['nama_tujuan'], $gabung),
                    'cp_tujuan' => $tabel->addCell($k['cp_tujuan'], $gabung),
                    'kesenjangan' => $tabel->addCell($k['kesenjangan'], $gabung),
                    'hasil' => $tabel->addCell($k['hasil'], $gabung),
                    'catatan' => $tabel->addCell($k['catatan'], $gabung),
                ];

                if ($j > 0) {
                    continue;
                }

                $selTujuan['nama_tujuan']->addText($p->matakuliah->nama, self::TEKS, ['spaceAfter' => 0]);
                $this->daftar($selTujuan['cp_tujuan'], $p->matakuliah->cpmk->pluck('deskripsi'), bernomor: true);
                $selTujuan['kesenjangan']->addText((string) $nilai?->catatan_kesenjangan, self::TEKS, ['spaceAfter' => 0]);
                $selTujuan['hasil']->addText($nilai?->nilai !== null ? (string) $nilai->nilai : '', self::TEKS, $tengah);
                $selTujuan['catatan']->addText((string) $nilai?->catatan_reviewer, self::TEKS, ['spaceAfter' => 0]);
            }
        }
    }

    /** @param Collection<int, ?string> $baris */
    private function daftar(Cell $sel, Collection $baris, bool $bernomor = false): void
    {
        $baris = $baris->filter(fn ($b) => filled($b))->values();

        if ($baris->isEmpty()) {
            $sel->addText('', self::TEKS, ['spaceAfter' => 0]);

            return;
        }

        foreach ($baris as $i => $teks) {
            $awalan = $bernomor && $baris->count() > 1 ? ($i + 1).'. ' : '';
            $sel->addText($awalan.trim($teks), self::TEKS, ['spaceAfter' => 40]);
        }
    }

    private function blokTandaTangan(Section $section, Mahasiswa $mahasiswa): void
    {
        $prodi = trim($this->namaJenjang($mahasiswa->prodi->jenjang).' '.$mahasiswa->prodi->nama);
        $ttd = ['indentation' => ['left' => 9800], 'spaceAfter' => 0, 'keepNext' => true];

        $section->addText(config('rpl.institusi.kota').', '.now()->locale('id')->translatedFormat('d F Y'), [], $ttd + ['spaceBefore' => 360]);
        $section->addText("Program Studi {$prodi}", [], $ttd);
        $section->addText('Koordinator,', [], $ttd + ['spaceAfter' => 1100]);
        $section->addText('(.........................................)', [], $ttd);
        $section->addText('NIP.', [], ['indentation' => ['left' => 9800], 'spaceAfter' => 0]);
    }
}
