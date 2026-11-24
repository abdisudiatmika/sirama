<?php

namespace App\Http\Controllers\AdminProdi;

use App\Http\Controllers\Controller;
use App\Models\Asesor;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/** Assessor accounts are global, so any prodi admin may see their signatures and export the list. */
class AsesorController extends Controller
{
    public function tandaTangan(Asesor $asesor)
    {
        $path = $asesor->pathTandaTangan();
        abort_unless($path, 404);

        return response()->file($path, ['Cache-Control' => 'private, max-age=300']);
    }

    /** All active assessors with their contact data and signature image, one row each. */
    public function ekspor()
    {
        $asesor = Asesor::join('users', 'users.id', '=', 'asesor.user_id')
            ->select('asesor.*')
            ->with('user')
            ->withCount('penugasan')
            ->where('asesor.is_aktif', true)
            ->orderBy('users.name')
            ->get();

        $book = new Spreadsheet;
        $sheet = $book->getActiveSheet()->setTitle('Data Asesor');
        $sheet->setCellValue('A1', 'DATA ASESOR RPL — '.mb_strtoupper(config('rpl.institusi.nama')));
        $sheet->setCellValue('A2', $asesor->count().' asesor aktif · '.$asesor->filter(fn ($a) => $a->pathTandaTangan())->count().' sudah ada TTD · diunduh '.now('Asia/Makassar')->format('d-m-Y H:i').' WITA');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $sheet->getStyle('A2')->getFont()->setItalic(true)->getColor()->setRGB('64748B');

        $kepala = ['No', 'Nama', 'Username', 'Email', 'NIP/NIDN', 'Bidang Keahlian', 'No. HP', 'Penugasan (mahasiswa)', 'Status TTD', 'Tanda Tangan'];
        $sheet->fromArray($kepala, null, 'A4');
        $sheet->getStyle('A4:J4')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4338CA']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);

        $baris = 5;
        foreach ($asesor as $i => $a) {
            $path = $a->pathTandaTangan();
            $sheet->fromArray([
                $i + 1, $a->user->name, $a->user->username, $a->user->email, $a->nidn, $a->bidang_keahlian,
                $a->no_hp, $a->penugasan_count, $path ? 'Ada' : 'Belum ada',
            ], null, "A{$baris}");
            // Numbers such as NIP or phone keep their leading zeros.
            $sheet->setCellValueExplicit("E{$baris}", (string) $a->nidn, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("G{$baris}", (string) $a->no_hp, DataType::TYPE_STRING);

            if ($path) {
                $gambar = new Drawing;
                $gambar->setPath($path);
                $gambar->setHeight(48);
                $gambar->setCoordinates("J{$baris}");
                $gambar->setOffsetX(6);
                $gambar->setOffsetY(4);
                $gambar->setWorksheet($sheet);
                $sheet->getRowDimension($baris)->setRowHeight(44);
            } else {
                $sheet->getStyle("I{$baris}")->getFont()->getColor()->setRGB('BE123C');
            }
            $baris++;
        }

        $akhir = max(4, $baris - 1);
        $sheet->getStyle("A4:J{$akhir}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('CBD5E1');
        $sheet->getStyle("A5:J{$akhir}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        $sheet->getStyle("A5:A{$akhir}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("H5:I{$akhir}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        foreach (['A' => 5, 'B' => 42, 'C' => 18, 'D' => 30, 'E' => 20, 'F' => 24, 'G' => 16, 'H' => 13, 'I' => 11, 'J' => 26] as $kolom => $lebar) {
            $sheet->getColumnDimension($kolom)->setWidth($lebar);
        }
        $sheet->getRowDimension(4)->setRowHeight(30);
        $sheet->freezePane('C5');
        $sheet->setAutoFilter("A4:I{$akhir}");

        return response()->streamDownload(
            fn () => (new Xlsx($book))->save('php://output'),
            'Data-Asesor-'.now('Asia/Makassar')->format('Ymd-Hi').'.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }
}
