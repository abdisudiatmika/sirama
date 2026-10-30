<?php

namespace App\Http\Controllers\AdminProdi;

use App\Http\Controllers\Controller;
use App\Models\Mahasiswa;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\TblWidth;
use PhpOffice\PhpWord\Style\Table;

class Form1WordController extends Controller
{
    private const LEBAR_LABEL = 3400;

    private const LEBAR_NILAI = 5900;

    public function __invoke(Mahasiswa $mahasiswa)
    {
        $prodiIds = Auth::user()->prodiDikelola()->pluck('prodi.id');
        abort_unless($prodiIds->contains($mahasiswa->prodi_id), 403);

        $mahasiswa->load(['user', 'biodata', 'pendidikanTerakhir']);
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

        // Without this, a "&" or "<" in user-entered data produces a corrupt .docx.
        Settings::setOutputEscapingEnabled(true);

        $word = new PhpWord;
        $word->setDefaultFontName('Times New Roman');
        $word->setDefaultFontSize(12);

        $section = $word->addSection(['marginTop' => 1134, 'marginBottom' => 1134, 'marginLeft' => 1418, 'marginRight' => 1134]);

        $section->addText('FORM 1', ['bold' => true], ['alignment' => Jc::END, 'spaceAfter' => 200]);
        $section->addText('FORMULIR APLIKASI REKOGNISI PEMBELAJARAN LAMPAU (RPL) TIPE A', ['bold' => true], ['alignment' => Jc::CENTER, 'spaceAfter' => 320]);
        $section->addText('Bagian 1 : Rincian Data Peserta / Calon peserta', ['bold' => true], ['spaceAfter' => 80]);
        $section->addText('Pada bagian ini, cantumkan data pribadi, data pendidikan formal serta data pekerjaan saudara pada saat ini.', [], ['alignment' => Jc::BOTH, 'spaceAfter' => 240]);

        $section->addText('a. Data Pribadi', ['bold' => true], ['spaceAfter' => 100]);
        $this->tabel($section, $dataPribadi, bergaris: true);

        $judulB = $section->addTextRun(['spaceBefore' => 280, 'spaceAfter' => 100, 'alignment' => Jc::BOTH]);
        $judulB->addText('b. Data Pendidikan Terakhir ', ['bold' => true]);
        $judulB->addText('(Hanya diisi dengan pendidikan formal Tingkat Sekolah Menengah Atas atau sederajat dan Perguruan Tinggi (apabila ada dilampiri foto kopi ijazah dan transkrip nilai).');
        $this->tabel($section, $dataPendidikan, bergaris: false);

        $namaFile = 'Form1-'.Str::slug($mahasiswa->user->name).'-'.$mahasiswa->nim.'.docx';
        $writer = IOFactory::createWriter($word, 'Word2007');

        return response()->streamDownload(
            fn () => $writer->save('php://output'),
            $namaFile,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        );
    }

    /** @param array<int, array{0: string, 1: mixed}> $baris */
    private function tabel($section, array $baris, bool $bergaris): void
    {
        $gaya = ['layout' => Table::LAYOUT_FIXED, 'width' => self::LEBAR_LABEL + self::LEBAR_NILAI, 'unit' => TblWidth::TWIP]
            + ($bergaris
                ? ['borderSize' => 6, 'borderColor' => '000000', 'cellMargin' => 80]
                : ['borderSize' => 0, 'borderColor' => 'FFFFFF', 'cellMargin' => 40]);

        $tabel = $section->addTable($gaya);
        $paragraf = ['spaceAfter' => 0];

        foreach ($baris as [$label, $nilai]) {
            $tabel->addRow(360);
            $tabel->addCell(self::LEBAR_LABEL)->addText($label, [], $paragraf);
            $tabel->addCell(self::LEBAR_NILAI)->addText(': '.(filled($nilai) ? $nilai : ''), [], $paragraf);
        }
    }
}
