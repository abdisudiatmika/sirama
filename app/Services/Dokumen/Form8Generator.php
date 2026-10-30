<?php

namespace App\Services\Dokumen;

use App\Models\Mahasiswa;
use App\Models\PengajuanMk;
use App\Services\PenilaianService;
use App\Services\RekapitulasiService;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\TblWidth;
use PhpOffice\PhpWord\Style\Table;

/**
 * Rekapitulasi Asesmen Perolehan SKS. Rows come from RekapitulasiService so
 * the grade and status always match the recap page. Credits are only
 * counted once the pleno has recognised a course: as "Transfer" when it went
 * through the formal route (Form 6), otherwise as "Perolehan" (Form 7).
 */
class Form8Generator extends FormulirWord
{
    private const KOLOM = ['no' => 450, 'kode' => 1150, 'nama' => 2700, 'transfer' => 900, 'perolehan' => 900, 'huruf' => 800, 'status' => 1100, 'keterangan' => 1300];

    private const TEKS = ['size' => 10];

    private const TENGAH = ['alignment' => Jc::CENTER, 'spaceAfter' => 0];

    public function __construct(
        private RekapitulasiService $rekap,
        private PenilaianService $penilaian,
    ) {}

    public function nomor(): int
    {
        return 8;
    }

    protected function isi(Section $section, Mahasiswa $mahasiswa): void
    {
        $data = $this->rekap->untuk($mahasiswa);
        $mahasiswa->loadMissing(['pengajuanMk.bukti', 'pengajuanMk.mkAsal']);
        $b = $mahasiswa->biodata;
        $p = $mahasiswa->pendidikanTerakhir;

        $section->addText('FORMULIR REKAPITULASI ASESMEN PEROLEHAN SKS', ['bold' => true, 'size' => 13], ['alignment' => Jc::CENTER, 'spaceAfter' => 240]);

        $identitas = $section->addTable(['layout' => Table::LAYOUT_FIXED, 'width' => self::LEBAR_HALAMAN, 'unit' => TblWidth::TWIP, 'borderSize' => 0, 'borderColor' => 'FFFFFF', 'cellMargin' => 20]);
        foreach ([
            ['Nama Pemohon RPL', $b->nama_lengkap ?? $mahasiswa->user->name],
            ['Alamat', $b?->alamat_rumah],
            ['No HP', $b?->telp_rumah_hp],
            ['e-mail', $b->email ?? $mahasiswa->user->email],
            ['Jenis Pendidikan sebelumnya', $p ? ($p->program ?: (filled($p->nama_pt) ? 'Perguruan Tinggi' : 'SMA/SMK sederajat')) : null],
            ['Program Pendidikan sebelumnya', $p ? collect([$p->jurusan_prodi, filled($p->nama_pt) ? $p->nama_pt : $p->nama_sekolah])->filter()->implode(', ') : null],
        ] as [$label, $nilai]) {
            $identitas->addRow();
            $identitas->addCell(3300)->addText($label, [], ['spaceAfter' => 0]);
            $identitas->addCell(self::LEBAR_HALAMAN - 3300)->addText(': '.($nilai ?? ''), [], ['spaceAfter' => 0]);
        }

        $section->addTextBreak();

        // Official catalog order (imported courses have no semester; ids follow the official order).
        $baris = $data['baris']->sortBy([
            fn ($a, $b) => ($a->matakuliah->semester === null) <=> ($b->matakuliah->semester === null),
            fn ($a, $b) => $a->matakuliah->semester <=> $b->matakuliah->semester,
            fn ($a, $b) => $a->matakuliah->id <=> $b->matakuliah->id,
        ])->values();

        $this->tabel($section, $baris);

        $section->addText('* Status diisi dari hasil rapat pleno program studi.', ['italic' => true, 'size' => 9], ['spaceBefore' => 120]);
    }

    private function tabel(Section $section, $baris): void
    {
        $k = self::KOLOM;
        $abu = ['bgColor' => 'D9D9D9', 'valign' => 'center'];
        $kepala = ['bold' => true, 'size' => 10];

        $tabel = $section->addTable([
            'layout' => Table::LAYOUT_FIXED,
            'width' => self::LEBAR_HALAMAN,
            'unit' => TblWidth::TWIP,
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 50,
        ]);

        $tabel->addRow(null, ['tblHeader' => true]);
        $tabel->addCell($k['no'], $abu + ['vMerge' => 'restart'])->addText('No', $kepala, self::TENGAH);
        $tabel->addCell($k['kode'], $abu + ['vMerge' => 'restart'])->addText('Kode Mata Kuliah', $kepala, self::TENGAH);
        $tabel->addCell($k['nama'], $abu + ['vMerge' => 'restart'])->addText('Nama Mata Kuliah', $kepala, self::TENGAH);
        $tabel->addCell($k['transfer'] + $k['perolehan'], $abu + ['gridSpan' => 2])->addText('Hasil Asesmen', $kepala, self::TENGAH);
        $tabel->addCell($k['huruf'], $abu + ['vMerge' => 'restart'])->addText('Nilai huruf', $kepala, self::TENGAH);
        $status = $tabel->addCell($k['status'], $abu + ['vMerge' => 'restart']);
        $status->addText('Status*', $kepala, self::TENGAH);
        $status->addText('(diisi dari hasil rapat pleno)', ['size' => 8], self::TENGAH);
        $tabel->addCell($k['keterangan'], $abu + ['vMerge' => 'restart'])->addText('Keterangan', $kepala, self::TENGAH);

        $tabel->addRow(null, ['tblHeader' => true]);
        foreach (['no', 'kode', 'nama'] as $kunci) {
            $tabel->addCell($k[$kunci], $abu + ['vMerge' => 'continue']);
        }
        $tabel->addCell($k['transfer'], $abu)->addText('Transfer sks', $kepala, self::TENGAH);
        $tabel->addCell($k['perolehan'], $abu)->addText('Perolehan sks', $kepala, self::TENGAH);
        foreach (['huruf', 'status', 'keterangan'] as $kunci) {
            $tabel->addCell($k[$kunci], $abu + ['vMerge' => 'continue']);
        }

        $total = ['transfer' => 0, 'perolehan' => 0];

        foreach ($baris as $i => $r) {
            $mk = $r->matakuliah;
            $diakui = $r->status === 'Direkognisi';
            $jalur = $r->pengajuan ? $this->jalur($r->pengajuan) : null;

            if ($diakui && $jalur) {
                $total[$jalur] += (int) $mk->sks;
            }

            $tabel->addRow(null, ['cantSplit' => true]);
            $tabel->addCell($k['no'])->addText((string) ($i + 1), self::TEKS, self::TENGAH);
            $tabel->addCell($k['kode'])->addText($mk->kode, self::TEKS, ['spaceAfter' => 0]);
            $tabel->addCell($k['nama'])->addText($mk->nama, self::TEKS, ['spaceAfter' => 0]);
            $tabel->addCell($k['transfer'])->addText($diakui && $jalur === 'transfer' ? (string) $mk->sks : '', self::TEKS, self::TENGAH);
            $tabel->addCell($k['perolehan'])->addText($diakui && $jalur === 'perolehan' ? (string) $mk->sks : '', self::TEKS, self::TENGAH);
            $tabel->addCell($k['huruf'])->addText($r->rata_rata !== null ? $this->penilaian->konversiHuruf((int) $r->rata_rata)['huruf'] : '', self::TEKS, self::TENGAH);
            $tabel->addCell($k['status'])->addText((string) $r->status, self::TEKS, self::TENGAH);
            $tabel->addCell($k['keterangan'])->addText($this->keterangan($r), ['size' => 8], ['spaceAfter' => 0]);
        }

        $tebal = ['bold' => true, 'size' => 10];
        $tabel->addRow();
        $tabel->addCell($k['no'] + $k['kode'] + $k['nama'], ['gridSpan' => 3])->addText('Jumlah SKS diakui', $tebal, ['alignment' => Jc::END, 'spaceAfter' => 0]);
        $tabel->addCell($k['transfer'])->addText((string) $total['transfer'], $tebal, self::TENGAH);
        $tabel->addCell($k['perolehan'])->addText((string) $total['perolehan'], $tebal, self::TENGAH);
        $tabel->addCell($k['huruf'] + $k['status'] + $k['keterangan'], ['gridSpan' => 3])->addText('Total: '.($total['transfer'] + $total['perolehan']).' SKS', $tebal, ['spaceAfter' => 0]);
    }

    /** 'transfer' when the formal route applies (Form 6), otherwise 'perolehan' (Form 7). */
    private function jalur(PengajuanMk $pengajuan): string
    {
        $formal = $pengajuan->mkAsal->isNotEmpty()
            || $pengajuan->bukti->contains('kategori', 'formal')
            || $pengajuan->penilaian->contains('jenis', 'formal');

        return $formal ? 'transfer' : 'perolehan';
    }

    private function keterangan(object $r): string
    {
        if (! $r->pengajuan) {
            return 'Tidak diajukan';
        }

        if ($r->status || $r->rata_rata === null) {
            return $r->rata_rata === null ? 'Belum dinilai asesor' : '';
        }

        return "Usulan: {$r->usulan} ({$r->rata_rata}; {$r->jumlah_penilai}/{$r->total_asesor} asesor)";
    }
}
