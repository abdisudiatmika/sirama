<?php

namespace App\Exports;

use App\Models\Mahasiswa;
use App\Services\PenilaianService;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;

/**
 * Recognised courses in the SIAKAD upload layout (Template_MK_RPL): one row
 * per prior course behind each course the pleno marked "Direkognisi". A
 * recognised course without prior-course data still gets one row, with the
 * prior-course columns left blank for the admin to complete.
 */
class RekognisiSiakadExport extends DefaultValueBinder implements FromArray, ShouldAutoSize, WithCustomValueBinder, WithHeadings
{
    /** SKS columns stay numeric; everything else is text so NIM and codes keep their exact form. */
    private const KOLOM_ANGKA = ['D', 'I'];

    public function __construct(
        private int $prodiId,
        private ?int $skemaId,
        private PenilaianService $penilaian,
        private ?int $mahasiswaId = null,
    ) {}

    public function headings(): array
    {
        return [
            'NIM MAHASISWA', 'MK KODE ASAL', 'MK NAMA ASAL', 'MK SKS ASAL', 'MK NILAI HURUF ASAL',
            'MK KODE PNB', 'MK NILAI HURUF PNB', 'NILAI INDEKS DIAKUI', 'MK SKS PNB', 'MK NAMA PNB',
        ];
    }

    public function array(): array
    {
        $mahasiswa = Mahasiswa::where('prodi_id', $this->prodiId)
            ->when($this->skemaId, fn ($q) => $q->where('skema_id', $this->skemaId))
            ->when($this->mahasiswaId, fn ($q) => $q->whereKey($this->mahasiswaId))
            ->whereHas('hasilPleno', fn ($q) => $q->where('status', 'Direkognisi'))
            ->with([
                'hasilPleno' => fn ($q) => $q->where('status', 'Direkognisi'),
                'pengajuanMk.matakuliah',
                'pengajuanMk.mkAsal',
                'pengajuanMk.penilaian',
            ])
            ->orderBy('nim')
            ->get();

        $baris = [];

        foreach ($mahasiswa as $mhs) {
            $diakui = $mhs->hasilPleno->pluck('matakuliah_id')->flip();

            $pengajuan = $mhs->pengajuanMk
                ->filter(fn ($p) => $diakui->has($p->matakuliah_id))
                ->sortBy(fn ($p) => [$p->matakuliah->semester ?? PHP_INT_MAX, $p->matakuliah->id]);

            foreach ($pengajuan as $p) {
                $mk = $p->matakuliah;
                $rata = $this->penilaian->rataRataAsesmen($p)['rata_rata'];
                $konversi = $rata !== null ? $this->penilaian->konversiHuruf($rata) : null;
                $daftarAsal = $p->mkAsal->isEmpty() ? collect([null]) : $p->mkAsal;

                foreach ($daftarAsal as $asal) {
                    $baris[] = [
                        $mhs->nim,
                        $asal?->kode_asal,
                        $asal?->nama_mk_asal,
                        $asal?->sks_asal,
                        $asal?->nilai_huruf_asal,
                        $mk->kode,
                        $konversi['huruf'] ?? null,
                        $konversi ? number_format($konversi['bobot'], 2, '.', '') : null,
                        $mk->sks,
                        $mk->nama,
                    ];
                }
            }
        }

        return $baris;
    }

    public function bindValue(Cell $cell, $value): bool
    {
        if ($value !== null && $value !== '' && ! in_array($cell->getColumn(), self::KOLOM_ANGKA, true)) {
            $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }
}
