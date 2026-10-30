<?php

namespace App\Services\Dokumen;

use App\Models\Mahasiswa;
use App\Models\Matakuliah;
use Illuminate\Support\Collection;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\TblWidth;
use PhpOffice\PhpWord\Style\Table;

class Form2Generator extends FormulirWord
{
    private const KOLOM = ['No.' => 700, 'Kode Mata Kuliah' => 1800, 'Judul Mata Kuliah' => 4400, 'Mengajukan RPL' => 2400];

    private const FONT_SIMBOL = ['name' => 'Segoe UI Symbol'];

    public function nomor(): int
    {
        return 2;
    }

    protected function isi(Section $section, Mahasiswa $mahasiswa): void
    {
        $mahasiswa->loadMissing(['user', 'prodi', 'skema', 'biodata', 'pengajuanMk']);
        $prodi = $mahasiswa->prodi->nama;
        $diajukan = $mahasiswa->pengajuanMk->pluck('matakuliah_id')->flip();

        $section->addText(
            "Pada bagian 2 ini, cantumkan Mata Kuliah pada Program Pendidikan {$prodi} yang saudara ajukan untuk memperoleh pengakuan berdasarkan kompetensi yang sudah saudara peroleh dari pengalaman kerja, kursus, pelatihan kerja atau lainnya di masa lampau (sebelum melamar RPL) dengan cara memberi tanda Ya atau Tidak.",
            [],
            ['alignment' => Jc::BOTH, 'spaceAfter' => 160],
        );

        $identitas = $section->addTextRun(['spaceAfter' => 160]);
        $identitas->addText('Nama Peserta : ', ['bold' => true]);
        $identitas->addText(($mahasiswa->biodata->nama_lengkap ?? $mahasiswa->user->name).' (NIM '.$mahasiswa->nim.')');

        $section->addText("Daftar Mata Kuliah Program Studi {$prodi}", ['bold' => true], ['spaceAfter' => 120]);

        $tabel = $section->addTable([
            'layout' => Table::LAYOUT_FIXED,
            'width' => self::LEBAR_HALAMAN,
            'unit' => TblWidth::TWIP,
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 60,
        ]);

        $tengah = ['alignment' => Jc::CENTER, 'spaceAfter' => 0];
        $kiri = ['spaceAfter' => 0];

        $tabel->addRow(null, ['tblHeader' => true]);
        foreach (self::KOLOM as $judul => $lebar) {
            $tabel->addCell($lebar, ['bgColor' => 'D9D9D9', 'valign' => 'center'])->addText($judul, ['bold' => true], $tengah);
        }

        foreach ($this->daftarMatakuliah($mahasiswa)->values() as $i => $mk) {
            [$lebarNo, $lebarKode, $lebarJudul, $lebarPilihan] = array_values(self::KOLOM);
            $ya = $diajukan->has($mk->id);

            $tabel->addRow(null, ['cantSplit' => true]);
            $tabel->addCell($lebarNo)->addText((string) ($i + 1), [], $tengah);
            $tabel->addCell($lebarKode)->addText($mk->kode, [], $kiri);
            $tabel->addCell($lebarJudul)->addText($mk->nama, [], $kiri);

            $pilihan = $tabel->addCell($lebarPilihan)->addTextRun($tengah);
            $pilihan->addText($ya ? '☑' : '☐', self::FONT_SIMBOL);
            $pilihan->addText(' Ya    ');
            $pilihan->addText($ya ? '☐' : '☑', self::FONT_SIMBOL);
            $pilihan->addText(' Tidak');
        }
    }

    /** @return Collection<int, Matakuliah> */
    private function daftarMatakuliah(Mahasiswa $mahasiswa): Collection
    {
        // Imported courses have no semester, but their ids follow the official catalog order.
        $query = $mahasiswa->skema
            ? $mahasiswa->skema->matakuliah()
            : Matakuliah::where('prodi_id', $mahasiswa->prodi_id)->where('is_aktif', true);

        return $query->orderByRaw('semester is null')->orderBy('semester')->orderBy('matakuliah.id')->get();
    }
}
