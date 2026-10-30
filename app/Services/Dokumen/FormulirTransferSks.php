<?php

namespace App\Services\Dokumen;

use App\Models\Mahasiswa;
use App\Models\PengajuanMk;
use App\Services\RekapitulasiService;
use Illuminate\Support\Collection;
use PhpOffice\PhpWord\Element\Cell;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\Element\Table as TabelWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\TblWidth;
use PhpOffice\PhpWord\Style\Table;

/**
 * Shared layout of the two credit-transfer forms (Form 6 formal, Form 7
 * non-formal): same header, identity block and coordinator signature; each
 * subclass draws its own table from that assessor's assessment of one type.
 */
abstract class FormulirTransferSks extends FormulirWord
{
    use VarianPerAsesor;

    protected const TEKS = ['size' => 9];

    protected const KEPALA = ['bold' => true, 'size' => 9];

    protected const TENGAH = ['alignment' => Jc::CENTER, 'spaceAfter' => 0];

    public function __construct(private RekapitulasiService $rekap) {}

    abstract protected function judul(): string;

    /** 'formal' or 'informal' — which penilaian rows this form reports. */
    abstract protected function jenisPenilaian(): string;

    abstract protected function termasuk(PengajuanMk $pengajuan): bool;

    abstract protected function tabel(Section $section, Collection $pengajuan, ?int $asesorId): void;

    protected function lanskap(): bool
    {
        return true;
    }

    protected function isi(Section $section, Mahasiswa $mahasiswa): void
    {
        $mahasiswa->loadMissing(['user', 'prodi', 'biodata', 'pendidikanTerakhir']);
        $penugasan = $this->penugasanVarian($mahasiswa);
        $b = $mahasiswa->biodata;

        $section->addText($this->judul(), ['bold' => true, 'size' => 13], ['alignment' => Jc::CENTER, 'spaceAfter' => 240]);

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
        $this->tabel($section, $this->pengajuan($mahasiswa), $penugasan?->asesor_id);
        $this->blokTandaTangan($section, $mahasiswa);
    }

    /** @return Collection<int, PengajuanMk> */
    private function pengajuan(Mahasiswa $mahasiswa): Collection
    {
        // Same catalog order as Form 2 (imported courses have no semester, ids follow the official order).
        return $mahasiswa->pengajuanMk()
            ->join('matakuliah', 'matakuliah.id', '=', 'pengajuan_mk.matakuliah_id')
            ->orderByRaw('matakuliah.semester is null')->orderBy('matakuliah.semester')->orderBy('matakuliah.id')
            ->select('pengajuan_mk.*')
            ->with([
                'matakuliah.cpmk' => fn ($q) => $q->orderBy('urutan')->orderBy('id'),
                'mkAsal',
                'bukti',
                'penilaian' => fn ($q) => $q->where('jenis', $this->jenisPenilaian()),
            ])
            ->get()
            ->filter(fn (PengajuanMk $p) => $this->termasuk($p))
            ->values();
    }

    protected function tabelBaru(Section $section): TabelWord
    {
        return $section->addTable([
            'layout' => Table::LAYOUT_FIXED,
            'width' => self::LEBAR_HALAMAN_LANSKAP,
            'unit' => TblWidth::TWIP,
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 50,
        ]);
    }

    protected function barisKosong(TabelWord $tabel, int $jumlahKolom, string $pesan): void
    {
        $tabel->addRow();
        $tabel->addCell(self::LEBAR_HALAMAN_LANSKAP, ['gridSpan' => $jumlahKolom])->addText($pesan, ['italic' => true, 'size' => 9], self::TENGAH);
    }

    /** @param Collection<int, ?string> $baris */
    protected function daftar(Cell $sel, Collection $baris, bool $bernomor = false): void
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
