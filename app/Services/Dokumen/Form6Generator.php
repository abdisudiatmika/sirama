<?php

namespace App\Services\Dokumen;

use App\Models\PengajuanMk;
use Illuminate\Support\Collection;
use PhpOffice\PhpWord\Element\Section;

/**
 * Asesmen Transfer SKS dari Pendidikan Formal: the student's prior courses
 * (mk_asal) next to the target course, with that assessor's formal result.
 */
class Form6Generator extends FormulirTransferSks
{
    private const KOLOM = [
        'no' => 500, 'kode_asal' => 1100, 'nama_asal' => 1800, 'cp_asal' => 3000,
        'nama_tujuan' => 1800, 'cp_tujuan' => 3000, 'kesenjangan' => 1300, 'hasil' => 700, 'catatan' => 1300,
    ];

    public function nomor(): int
    {
        return 6;
    }

    protected function judul(): string
    {
        return 'ASESMEN TRANSFER SKS DARI PENDIDIKAN FORMAL';
    }

    protected function jenisPenilaian(): string
    {
        return 'formal';
    }

    /** Courses with prior-course data, formal evidence or a formal assessment. */
    protected function termasuk(PengajuanMk $p): bool
    {
        return $p->mkAsal->isNotEmpty()
            || $p->penilaian->isNotEmpty()
            || $p->bukti->contains('kategori', 'formal');
    }

    protected function tabel(Section $section, Collection $pengajuan, ?int $asesorId): void
    {
        $k = self::KOLOM;
        $tabel = $this->tabelBaru($section);
        $abu = ['bgColor' => 'D9D9D9', 'valign' => 'center'];

        $tabel->addRow(null, ['tblHeader' => true]);
        $tabel->addCell($k['no'], $abu + ['vMerge' => 'restart'])->addText('No', self::KEPALA, self::TENGAH);
        $tabel->addCell($k['kode_asal'] + $k['nama_asal'] + $k['cp_asal'], $abu + ['gridSpan' => 3])->addText('Pemohon', self::KEPALA, self::TENGAH);
        $tabel->addCell($k['nama_tujuan'] + $k['cp_tujuan'], $abu + ['gridSpan' => 2])->addText('Prodi yang dituju', self::KEPALA, self::TENGAH);
        $tabel->addCell($k['kesenjangan'], $abu + ['vMerge' => 'restart'])->addText('Kesenjangan', self::KEPALA, self::TENGAH);
        $tabel->addCell($k['hasil'], $abu + ['vMerge' => 'restart'])->addText('Hasil', self::KEPALA, self::TENGAH);
        $tabel->addCell($k['catatan'], $abu + ['vMerge' => 'restart'])->addText('Catatan Asesor', self::KEPALA, self::TENGAH);

        $tabel->addRow(null, ['tblHeader' => true]);
        $tabel->addCell($k['no'], $abu + ['vMerge' => 'continue']);
        foreach (['kode_asal' => 'Kode MK', 'nama_asal' => 'Nama Mata kuliah', 'cp_asal' => 'Capaian Pembelajaran Matakuliah', 'nama_tujuan' => 'Nama Matakuliah', 'cp_tujuan' => 'Capaian Pembelajaran Matakuliah'] as $kunci => $judul) {
            $tabel->addCell($k[$kunci], $abu)->addText($judul, self::KEPALA, self::TENGAH);
        }
        foreach (['kesenjangan', 'hasil', 'catatan'] as $kunci) {
            $tabel->addCell($k[$kunci], $abu + ['vMerge' => 'continue']);
        }

        if ($pengajuan->isEmpty()) {
            $this->barisKosong($tabel, 9, 'Tidak ada mata kuliah yang diajukan melalui jalur pendidikan formal.');

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
                    $sel->addText((string) ($i + 1), self::TEKS, self::TENGAH);
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
                $selTujuan['hasil']->addText($nilai?->nilai !== null ? (string) $nilai->nilai : '', self::TEKS, self::TENGAH);
                $selTujuan['catatan']->addText((string) $nilai?->catatan_reviewer, self::TEKS, ['spaceAfter' => 0]);
            }
        }
    }
}
