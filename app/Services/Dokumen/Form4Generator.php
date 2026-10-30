<?php

namespace App\Services\Dokumen;

use App\Models\Mahasiswa;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\Element\Table as TabelWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\TblWidth;
use PhpOffice\PhpWord\Style\Table;

/**
 * Daftar Riwayat Hidup. Identity and education are prefilled; the training,
 * seminar, award, organisation and work tables are left blank because the
 * system holds no structured data for them — the student completes and
 * signs the form, then submits it as a Drive link.
 */
class Form4Generator extends FormulirWord
{
    private const LEBAR_LABEL = 3000;

    private const BARIS_KOSONG = 3;

    public function nomor(): int
    {
        return 4;
    }

    protected function isi(Section $section, Mahasiswa $mahasiswa): void
    {
        $mahasiswa->loadMissing(['user', 'biodata', 'pendidikanTerakhir']);
        $b = $mahasiswa->biodata;
        $p = $mahasiswa->pendidikanTerakhir;
        $nama = $b->nama_lengkap ?? $mahasiswa->user->name;

        $section->addText('FORMULIR DAFTAR RIWAYAT HIDUP', ['bold' => true, 'size' => 13], ['alignment' => Jc::CENTER, 'spaceAfter' => 280]);

        $this->judulBagian($section, 'IDENTITAS DIRI');
        $identitas = $this->tabel($section, garis: false);
        foreach ([
            ['Nama', $nama],
            ['Tempat dan Tanggal Lahir', collect([$b?->tempat_lahir, $b?->tanggal_lahir?->locale('id')->translatedFormat('d F Y')])->filter()->implode(', ')],
            ['Jenis Kelamin', $b?->jenis_kelamin],
            ['Pekerjaan', null],
            ['Alamat Pekerjaan', $b?->alamat_kantor],
            ['Alamat Rumah', $b?->alamat_rumah],
            ['Telp./HP', $b?->telp_rumah_hp],
        ] as [$label, $nilai]) {
            $identitas->addRow(340);
            $identitas->addCell(self::LEBAR_LABEL)->addText($label, [], ['spaceAfter' => 0]);
            $identitas->addCell(self::LEBAR_HALAMAN - self::LEBAR_LABEL)->addText(': '.($nilai ?? ''), [], ['spaceAfter' => 0]);
        }

        $this->judulBagian($section, 'RIWAYAT PENDIDIKAN');
        $pendidikan = array_values(array_filter([
            $p?->nama_sekolah ? [$p->nama_sekolah, $p->tahun_lulus_sekolah, null] : null,
            $p?->nama_pt ? [$p->nama_pt, $p->tahun_lulus_pt, trim(($p->program ? $p->program.' ' : '').$p->jurusan_prodi)] : null,
        ]));
        $this->tabelIsian(
            $section,
            ['NO' => 700, 'Nama Sekolah' => 3700, 'Tahun Lulus' => 1500, 'Jurusan/Program Studi' => 3400],
            array_map(fn ($baris, $i) => [(string) ($i + 1), ...$baris], $pendidikan, array_keys($pendidikan)),
            minimalBaris: 4,
            nomorOtomatis: true,
        );

        $this->judulBagian($section, 'PELATIHAN PROFESIONAL');
        $this->tabelIsian($section, ['Tahun' => 1100, 'Jenis Pelatihan (Dalam/ Luar Negeri)' => 3400, 'Penyelenggara' => 2900, 'Jangka waktu' => 1900]);

        $this->judulBagian($section, 'KONFERENSI/SEMINAR/LOKAKARYA/SIMPOSIUM');
        $this->tabelIsian($section, ['Tahun' => 1100, 'Judul Kegiatan' => 3400, 'Penyelenggara' => 2600, 'Panitia/ peserta/ pembicara' => 2200]);

        $this->judulBagian($section, 'PENGHARGAAN/PIAGAM');
        $this->tabelIsian($section, ['Tahun' => 1100, 'Bentuk Penghargaan' => 4600, 'Pemberi' => 3600]);

        $this->judulBagian($section, 'ORGANISASI PROFESI/ILMIAH');
        $this->tabelIsian($section, ['Tahun' => 1100, 'Jenis/ Nama Organisasi' => 4600, 'Jabatan/ jenjang keanggotaan' => 3600]);

        $this->judulBagian($section, 'RIWAYAT PEKERJAAN');
        $this->tabelPekerjaan($section);

        $section->addText(
            'Saya menyatakan bahwa semua keterangan dalam Daftar Riwayat Hidup ini adalah sepenuhnya benar dan saya bertanggung-jawab atas seluruh data dalam formulir ini dan apabila dikemudian hari ternyata informasi yang saya sampaikan tersebut adalah tidak benar, maka saya bersedia menerima sangsi sesuai dengan ketentuan yang berlaku dan bersedia mempertanggung jawabkannya.',
            [],
            ['alignment' => Jc::BOTH, 'spaceBefore' => 280, 'spaceAfter' => 280],
        );

        $ttd = ['alignment' => Jc::START, 'indentation' => ['left' => 5200], 'spaceAfter' => 0];
        $section->addText('......................., ..........................', [], $ttd);
        $section->addText('Yang Menyatakan,', [], $ttd + ['spaceAfter' => 1100]);
        $section->addText($nama, ['bold' => true, 'underline' => 'single'], $ttd);
    }

    private function judulBagian(Section $section, string $judul): void
    {
        $section->addText($judul, ['bold' => true], ['spaceBefore' => 240, 'spaceAfter' => 100, 'keepNext' => true]);
    }

    private function tabel(Section $section, bool $garis = true): TabelWord
    {
        return $section->addTable(['layout' => Table::LAYOUT_FIXED, 'width' => self::LEBAR_HALAMAN, 'unit' => TblWidth::TWIP]
            + ($garis
                ? ['borderSize' => 6, 'borderColor' => '000000', 'cellMargin' => 60]
                : ['borderSize' => 0, 'borderColor' => 'FFFFFF', 'cellMargin' => 30]));
    }

    /**
     * @param  array<string, int>  $kolom  header => width in twips
     * @param  list<list<?string>>  $isi
     */
    private function tabelIsian(Section $section, array $kolom, array $isi = [], int $minimalBaris = self::BARIS_KOSONG, bool $nomorOtomatis = false): void
    {
        $tabel = $this->tabel($section);
        $tengah = ['alignment' => Jc::CENTER, 'spaceAfter' => 0];

        $tabel->addRow(null, ['tblHeader' => true]);
        foreach ($kolom as $judul => $lebar) {
            $tabel->addCell($lebar, ['bgColor' => 'D9D9D9', 'valign' => 'center'])->addText($judul, ['bold' => true], $tengah);
        }

        $jumlah = max($minimalBaris, count($isi));
        for ($i = 0; $i < $jumlah; $i++) {
            $baris = $isi[$i] ?? ($nomorOtomatis ? [(string) ($i + 1)] : []);
            $tabel->addRow(420, ['cantSplit' => true]);
            foreach (array_values($kolom) as $j => $lebar) {
                $tabel->addCell($lebar)->addText((string) ($baris[$j] ?? ''), [], $nomorOtomatis && $j === 0 ? $tengah : ['spaceAfter' => 0]);
            }
        }
    }

    private function tabelPekerjaan(Section $section): void
    {
        $lebar = ['perusahaan' => 2800, 'mulai' => 1250, 'akhir' => 1250, 'jabatan' => 1700, 'uraian' => 2300];
        $abu = ['bgColor' => 'D9D9D9', 'valign' => 'center'];
        $tebal = ['bold' => true];
        $tengah = ['alignment' => Jc::CENTER, 'spaceAfter' => 0];

        $tabel = $this->tabel($section);

        $tabel->addRow(null, ['tblHeader' => true]);
        $tabel->addCell($lebar['perusahaan'], $abu + ['vMerge' => 'restart'])->addText('Nama, alamat dan nomor telpon Perusahaan', $tebal, $tengah);
        $tabel->addCell($lebar['mulai'] + $lebar['akhir'], $abu + ['gridSpan' => 2])->addText('Periode kerja (DD/MM/YYYY)', $tebal, $tengah);
        $tabel->addCell($lebar['jabatan'], $abu + ['vMerge' => 'restart'])->addText('Jabatan/ Posisi', $tebal, $tengah);
        $tabel->addCell($lebar['uraian'], $abu + ['vMerge' => 'restart'])->addText('Uraian Pekerjaan/ tugas', $tebal, $tengah);

        $tabel->addRow(null, ['tblHeader' => true]);
        $tabel->addCell($lebar['perusahaan'], $abu + ['vMerge' => 'continue']);
        $tabel->addCell($lebar['mulai'], $abu)->addText('Mulai', $tebal, $tengah);
        $tabel->addCell($lebar['akhir'], $abu)->addText('Akhir', $tebal, $tengah);
        $tabel->addCell($lebar['jabatan'], $abu + ['vMerge' => 'continue']);
        $tabel->addCell($lebar['uraian'], $abu + ['vMerge' => 'continue']);

        for ($i = 0; $i < self::BARIS_KOSONG; $i++) {
            $tabel->addRow(700, ['cantSplit' => true]);
            foreach ($lebar as $w) {
                $tabel->addCell($w)->addText('', [], ['spaceAfter' => 0]);
            }
        }
    }
}
