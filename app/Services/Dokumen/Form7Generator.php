<?php

namespace App\Services\Dokumen;

use App\Models\Bukti;
use App\Models\PengajuanMk;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\Element\Section;

/**
 * Asesmen Transfer SKS dari Pendidikan Non-Formal: which kinds of evidence
 * the student attached to each course, next to the target course, with that
 * assessor's non-formal (informal) result.
 */
class Form7Generator extends FormulirTransferSks
{
    private const KOLOM = [
        'no' => 400, 'ijazah' => 900, 'transkrip' => 900, 'karya' => 1000, 'sertifikat' => 1100, 'pengalaman' => 1100,
        'nama_tujuan' => 1700, 'cp_tujuan' => 3500, 'kesenjangan' => 1400, 'hasil' => 600, 'catatan' => 1900,
    ];

    private const JENIS_BUKTI = [
        'ijazah' => 'Ijazah',
        'transkrip' => 'Transkrip',
        'karya' => 'Karya Monumental',
        'sertifikat' => 'Sertifikat kompetensi',
        'pengalaman' => 'Pengalaman kerja',
    ];

    public function nomor(): int
    {
        return 7;
    }

    protected function judul(): string
    {
        return 'ASESMEN TRANSFER SKS DARI PENDIDIKAN NON FORMAL';
    }

    protected function jenisPenilaian(): string
    {
        return 'informal';
    }

    /** Courses with non-formal evidence or a non-formal assessment. */
    protected function termasuk(PengajuanMk $p): bool
    {
        return $p->penilaian->isNotEmpty() || $p->bukti->contains('kategori', 'informal');
    }

    /**
     * Maps a free-text evidence type onto the form's five columns. "Video" counts
     * as work experience, as Form 5 lists it as "Video di tempat kerja".
     */
    private function kolomBukti(Bukti $bukti): ?string
    {
        // Placeholders created by the legacy migration don't say what was really attached.
        if (Str::startsWith((string) $bukti->url, 'DATA-LAMA')) {
            return null;
        }

        $jenis = Str::lower($bukti->jenis_bukti);

        return match (true) {
            Str::contains($jenis, ['ijazah', 'ijasah']) => 'ijazah',
            Str::contains($jenis, 'transkrip') => 'transkrip',
            Str::contains($jenis, 'karya') => 'karya',
            Str::contains($jenis, 'sertifikat') => 'sertifikat',
            Str::contains($jenis, ['pengalaman', 'video']) => 'pengalaman',
            default => null,
        };
    }

    /** Legacy attachment labels onto the same five columns (CV and declarations are not evidence types here). */
    private const KOLOM_LABEL_LAMA = [
        'ijazah' => 'ijazah',
        'transkrip' => 'transkrip',
        'sertifikat_kompetensi' => 'sertifikat',
        'sertifikat_pelatihan' => 'sertifikat',
        'pengalaman' => 'pengalaman',
        'video' => 'pengalaman',
    ];

    protected function tabel(Section $section, Collection $pengajuan, ?int $asesorId): void
    {
        $k = self::KOLOM;
        $tabel = $this->tabelBaru($section);
        $abu = ['bgColor' => 'D9D9D9', 'valign' => 'center'];
        $lebarPemohon = array_sum(array_intersect_key($k, self::JENIS_BUKTI));

        $tabel->addRow(null, ['tblHeader' => true]);
        $tabel->addCell($k['no'], $abu + ['vMerge' => 'restart'])->addText('No', self::KEPALA, self::TENGAH);
        $tabel->addCell($lebarPemohon, $abu + ['gridSpan' => count(self::JENIS_BUKTI)])->addText('Pemohon', self::KEPALA, self::TENGAH);
        $tabel->addCell($k['nama_tujuan'] + $k['cp_tujuan'], $abu + ['gridSpan' => 2])->addText('Prodi yang dituju', self::KEPALA, self::TENGAH);
        $tabel->addCell($k['kesenjangan'], $abu + ['vMerge' => 'restart'])->addText('Kesenjangan', self::KEPALA, self::TENGAH);
        $tabel->addCell($k['hasil'], $abu + ['vMerge' => 'restart'])->addText('Hasil', self::KEPALA, self::TENGAH);
        $tabel->addCell($k['catatan'], $abu + ['vMerge' => 'restart'])->addText('Catatan Asesor', self::KEPALA, self::TENGAH);

        $tabel->addRow(null, ['tblHeader' => true]);
        $tabel->addCell($k['no'], $abu + ['vMerge' => 'continue']);
        foreach (self::JENIS_BUKTI as $kunci => $judul) {
            $tabel->addCell($k[$kunci], $abu)->addText($judul, self::KEPALA, self::TENGAH);
        }
        $tabel->addCell($k['nama_tujuan'], $abu)->addText('Nama Matakuliah', self::KEPALA, self::TENGAH);
        $tabel->addCell($k['cp_tujuan'], $abu)->addText('Capaian Pembelajaran Matakuliah', self::KEPALA, self::TENGAH);
        foreach (['kesenjangan', 'hasil', 'catatan'] as $kunci) {
            $tabel->addCell($k[$kunci], $abu + ['vMerge' => 'continue']);
        }

        if ($pengajuan->isEmpty()) {
            $this->barisKosong($tabel, count($k), 'Tidak ada mata kuliah yang diajukan melalui jalur pendidikan non formal.');

            return;
        }

        foreach ($pengajuan as $i => $p) {
            $nilai = $asesorId ? $p->penilaian->firstWhere('asesor_id', $asesorId) : null;
            $adaBukti = $p->bukti->map(fn (Bukti $b) => $this->kolomBukti($b))
                ->concat($p->lampiranLama->map(fn ($l) => self::KOLOM_LABEL_LAMA[$l->label] ?? null))
                ->filter()->flip();

            $tabel->addRow(null, ['cantSplit' => true]);
            $tabel->addCell($k['no'])->addText((string) ($i + 1), self::TEKS, self::TENGAH);
            foreach (array_keys(self::JENIS_BUKTI) as $kunci) {
                $tabel->addCell($k[$kunci])->addText($adaBukti->has($kunci) ? '√' : '', self::TEKS, self::TENGAH);
            }
            $tabel->addCell($k['nama_tujuan'])->addText($p->matakuliah->nama, self::TEKS, ['spaceAfter' => 0]);
            $this->daftar($tabel->addCell($k['cp_tujuan']), $p->matakuliah->cpmk->pluck('deskripsi'), bernomor: true);
            $tabel->addCell($k['kesenjangan'])->addText((string) $nilai?->catatan_kesenjangan, self::TEKS, ['spaceAfter' => 0]);
            $tabel->addCell($k['hasil'])->addText($nilai?->nilai !== null ? (string) $nilai->nilai : '', self::TEKS, self::TENGAH);
            $tabel->addCell($k['catatan'])->addText((string) $nilai?->catatan_reviewer, self::TEKS, ['spaceAfter' => 0]);
        }
    }
}
