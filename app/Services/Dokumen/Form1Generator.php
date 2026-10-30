<?php

namespace App\Services\Dokumen;

use App\Models\Mahasiswa;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\TblWidth;
use PhpOffice\PhpWord\Style\Table;

class Form1Generator extends FormulirWord
{
    private const LEBAR_LABEL = 3400;

    public function nomor(): int
    {
        return 1;
    }

    protected function isi(Section $section, Mahasiswa $mahasiswa): void
    {
        $mahasiswa->loadMissing(['user', 'biodata', 'pendidikanTerakhir']);
        $b = $mahasiswa->biodata;
        $p = $mahasiswa->pendidikanTerakhir;

        $tempatTgl = collect([
            $b?->tempat_lahir,
            $b?->tanggal_lahir?->locale('id')->translatedFormat('d F Y'),
        ])->filter()->implode(', ');

        $dataPribadi = [
            ['Nama lengkap', $b->nama_lengkap ?? $mahasiswa->user->name],
            ['Tempat / tgl. Lahir', $tempatTgl],
            ['Jenis kelamin', $b?->jenis_kelamin],
            ['Status', $b?->status],
            ['Kebangsaan', $b?->kebangsaan],
            ['Alamat rumah', $b?->alamat_rumah],
            ['Kode pos', $b?->kode_pos],
            ['Telpon rumah/HP', $b?->telp_rumah_hp],
            ['Alamat kantor', $b?->alamat_kantor],
            ['Telpon kantor', $b?->telp_kantor],
            ['Alamat e-mail', $b->email ?? $mahasiswa->user->email],
        ];

        $dataPendidikan = [
            ['Nama Sekolah', $p?->nama_sekolah],
            ['Alamat Sekolah', $p?->alamat_sekolah],
            ['Tahun Lulus', $p?->tahun_lulus_sekolah],
            ['Nama Perguruan Tinggi', $p?->nama_pt],
            ['Jurusan/Program Studi', $p?->jurusan_prodi],
            ['Program', $p?->program],
            ['Tahun Lulus', $p?->tahun_lulus_pt],
        ];

        $section->addText('FORMULIR APLIKASI REKOGNISI PEMBELAJARAN LAMPAU (RPL) TIPE A', ['bold' => true], ['alignment' => Jc::CENTER, 'spaceAfter' => 320]);
        $section->addText('Bagian 1 : Rincian Data Peserta / Calon peserta', ['bold' => true], ['spaceAfter' => 80]);
        $section->addText('Pada bagian ini, cantumkan data pribadi, data pendidikan formal serta data pekerjaan saudara pada saat ini.', [], ['alignment' => Jc::BOTH, 'spaceAfter' => 240]);

        $section->addText('a. Data Pribadi', ['bold' => true], ['spaceAfter' => 100]);
        $this->tabel($section, $dataPribadi, bergaris: true);

        $judulB = $section->addTextRun(['spaceBefore' => 280, 'spaceAfter' => 100, 'alignment' => Jc::BOTH]);
        $judulB->addText('b. Data Pendidikan Terakhir ', ['bold' => true]);
        $judulB->addText('(Hanya diisi dengan pendidikan formal Tingkat Sekolah Menengah Atas atau sederajat dan Perguruan Tinggi (apabila ada dilampiri foto kopi ijazah dan transkrip nilai).');
        $this->tabel($section, $dataPendidikan, bergaris: false);
    }

    /** @param array<int, array{0: string, 1: mixed}> $baris */
    private function tabel(Section $section, array $baris, bool $bergaris): void
    {
        $gaya = ['layout' => Table::LAYOUT_FIXED, 'width' => self::LEBAR_HALAMAN, 'unit' => TblWidth::TWIP]
            + ($bergaris
                ? ['borderSize' => 6, 'borderColor' => '000000', 'cellMargin' => 80]
                : ['borderSize' => 0, 'borderColor' => 'FFFFFF', 'cellMargin' => 40]);

        $tabel = $section->addTable($gaya);
        $paragraf = ['spaceAfter' => 0];

        foreach ($baris as [$label, $nilai]) {
            $tabel->addRow(360);
            $tabel->addCell(self::LEBAR_LABEL)->addText($label, [], $paragraf);
            $tabel->addCell(self::LEBAR_HALAMAN - self::LEBAR_LABEL)->addText(': '.(filled($nilai) ? $nilai : ''), [], $paragraf);
        }
    }
}
