<?php

namespace App\Exports;

use App\Models\Mahasiswa;
use App\Services\RekapitulasiService;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Mirrors the same §6.5 rows as the web page and the PDF (all three pull
 * from RekapitulasiService), laid out as a plain worksheet an admin can
 * drop straight into a rapat pleno packet or re-sort/filter themselves.
 */
class RekapitulasiExport implements FromArray, ShouldAutoSize, WithEvents
{
    private array $data;

    private int $kolomTerakhir;

    private int $headerRow;

    public function __construct(Mahasiswa $mahasiswa, RekapitulasiService $service)
    {
        $this->data = $service->untuk($mahasiswa);
        $this->kolomTerakhir = 6 + $this->data['asesorUrutan']->count();
    }

    public function array(): array
    {
        $mahasiswa = $this->data['mahasiswa'];
        $asesorUrutan = $this->data['asesorUrutan'];
        $baris = $this->data['baris'];

        $rows = [];

        // A truly empty row (`[]`) gets silently dropped by the exporter,
        // which would throw off every row-position calculation below — a
        // single blank-space cell keeps the row real without being visible.
        $blank = [' '];

        $rows[] = [config('rpl.institusi.nama')];
        $rows[] = ['FORMULIR REKAPITULASI HASIL ASESMEN'];
        $rows[] = [$mahasiswa->prodi->nama.' — Jalur Rekognisi Pembelajaran Lampau'];
        $rows[] = $blank;
        $rows[] = ['Nama', $mahasiswa->biodata->nama_lengkap ?? $mahasiswa->user->name];
        $rows[] = ['NIM', $mahasiswa->nim];
        $rows[] = ['No HP', $mahasiswa->biodata->telp_rumah_hp ?? '—'];
        $rows[] = ['Email', $mahasiswa->biodata->email ?? $mahasiswa->user->email];
        $rows[] = ['Alamat', $mahasiswa->biodata->alamat_rumah ?? '—'];
        $rows[] = ['Jenjang Pendidikan Sebelumnya', $this->data['jenjangLabel']];
        $rows[] = $blank;

        $header = ['No', 'Kode MK', 'Matakuliah', 'Skor Mandiri'];
        foreach ($asesorUrutan as $penugasan) {
            $header[] = 'Asesor '.$penugasan->urutan;
        }
        $header[] = 'Rata-rata';
        $header[] = 'Skor Minimum';
        $header[] = 'Status';
        $rows[] = $header;
        $this->headerRow = count($rows);

        foreach ($baris as $i => $row) {
            $line = [
                $i + 1,
                $row->matakuliah->kode,
                $row->matakuliah->nama.($row->matakuliah->dapat_direkognisi ? ' *' : ''),
                $row->pengajuan?->nilai_mandiri_angka,
            ];

            foreach ($row->nilai_per_asesor as $nilai) {
                $line[] = $nilai;
            }

            $line[] = $row->rata_rata;
            $line[] = $row->matakuliah->skor_minimum;
            $line[] = $row->status ?? '';

            $rows[] = $line;
        }

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $lastCol = $this->colLetter($this->kolomTerakhir);
                $headerRow = $this->headerRow;
                $lastRow = $headerRow + $this->data['baris']->count();

                $sheet->mergeCells("A1:{$lastCol}1");
                $sheet->mergeCells("A2:{$lastCol}2");
                $sheet->mergeCells("A3:{$lastCol}3");
                $sheet->getStyle('A1:A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('A1')->getFont()->setSize(13)->setBold(true);
                $sheet->getStyle('A2')->getFont()->setSize(12)->setBold(true);
                $sheet->getStyle('A3')->getFont()->setItalic(true);

                $sheet->getStyle('A5:A10')->getFont()->setBold(true);

                $sheet->getStyle("A{$headerRow}:{$lastCol}{$headerRow}")->getFont()->setBold(true);
                $sheet->getStyle("A{$headerRow}:{$lastCol}{$headerRow}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('E2E8F0');

                $sheet->getStyle("A{$headerRow}:{$lastCol}{$lastRow}")
                    ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            },
        ];
    }

    private function colLetter(int $index): string
    {
        return Coordinate::stringFromColumnIndex($index);
    }
}
