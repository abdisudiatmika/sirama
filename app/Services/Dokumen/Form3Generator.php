<?php

namespace App\Services\Dokumen;

use App\Models\Mahasiswa;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\TblWidth;
use PhpOffice\PhpWord\Style\Tab;
use PhpOffice\PhpWord\Style\Table;

class Form3Generator extends FormulirWord
{
    private const BUTIR_PERNYATAAN = [
        'Semua informasi yang saya tuliskan adalah sepenuhnya benar dan saya bertanggung-jawab atas seluruh data dalam formulir ini dan apabila dikemudian hari ternyata informasi yang saya sampaikan tersebut adalah tidak benar, maka saya bersedia menerima sanksi sesuai dengan ketentuan yang berlaku.',
        'Saya memberikan ijin kepada Tim RPL, untuk melakukan pemeriksaan kebenaran informasi yang saya berikan dalam formulir aplikasi ini kepada seluruh pihak yang terkait dengan jenjang akademik sebelumnya dan kepada perusahaan tempat saya bekerja sebelumnya dan atau saat ini saya bekerja.',
        'Saya bersedia melengkapi berkas yang dibutuhkan untuk pelaksanaan proses asesmen dan rekognisi.',
        'Saya akan mengikuti proses asesmen dan rekognisi sesuai dengan kesepakatan waktu yang ditetapkan dan saya akan melunasi biaya pendaftaran setelah pengisian aplikasi ini selesai.',
    ];

    public function nomor(): int
    {
        return 3;
    }

    protected function isi(Section $section, Mahasiswa $mahasiswa): void
    {
        $mahasiswa->loadMissing(['user', 'prodi', 'biodata', 'pernyataan']);
        $pernyataan = $mahasiswa->pernyataan;
        $nama = $mahasiswa->biodata->nama_lengkap ?? $mahasiswa->user->name;
        $institusi = config('rpl.institusi.nama');

        $section->addText('Daftar Pelatihan dan Pengalaman Kerja', ['bold' => true], ['spaceAfter' => 80]);
        $section->addText('Terlampir pada Daftar Riwayat Hidup', [], ['spaceAfter' => 360]);

        $section->addText('Pernyataan Calon Peserta', ['bold' => true], ['alignment' => Jc::CENTER, 'spaceAfter' => 200]);
        $section->addText(
            "Saya telah membaca dan mengisi Formulir Pendaftaran untuk mengikuti proses RPL di Program Studi {$mahasiswa->prodi->nama} pada Perguruan Tinggi {$institusi} dengan benar dan dengan ini saya menyatakan:",
            [],
            ['alignment' => Jc::BOTH, 'spaceAfter' => 160],
        );

        foreach (self::BUTIR_PERNYATAAN as $i => $butir) {
            $section->addText(
                ($i + 1).".\t".$butir,
                [],
                ['alignment' => Jc::BOTH, 'spaceAfter' => 120, 'indentation' => ['left' => 400, 'hanging' => 400], 'tabs' => [new Tab('left', 400)]],
            );
        }

        $tempatTanggal = $pernyataan
            ? collect([$pernyataan->tempat, $pernyataan->tanggal?->locale('id')->translatedFormat('d F Y')])->filter()->implode(', ')
            : '';

        $kotak = $section->addTable([
            'layout' => Table::LAYOUT_FIXED,
            'width' => self::LEBAR_HALAMAN,
            'unit' => TblWidth::TWIP,
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 120,
        ]);
        $kotak->addRow();
        $sel = $kotak->addCell(self::LEBAR_HALAMAN);
        $sel->addText('', [], ['spaceBefore' => 200, 'spaceAfter' => 0]);
        $sel->addText('Tempat/Tanggal : '.$tempatTanggal, [], ['spaceAfter' => 160]);

        // The on-screen consent checkbox stands in for a wet signature, so state it where the signature goes.
        if ($pernyataan?->disetujui && $pernyataan->waktu_setuju) {
            $waktu = $pernyataan->waktu_setuju->copy()->setTimezone('Asia/Makassar')->locale('id')->translatedFormat('d F Y \p\u\k\u\l H.i');
            $sel->addText('Tanda tangan Calon Peserta :', [], ['spaceAfter' => 120]);
            $sel->addText(
                "DISETUJUI SECARA ELEKTRONIK melalui SIRAMA pada {$waktu} WITA",
                ['bold' => true, 'color' => '1F3A8A'],
                ['spaceAfter' => 240],
            );
        } else {
            $sel->addText('Tanda tangan Calon Peserta :', [], ['spaceAfter' => 900]);
        }

        $sel->addText('( '.$nama.' )', [], ['spaceAfter' => 120]);
    }
}
