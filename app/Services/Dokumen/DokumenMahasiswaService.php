<?php

namespace App\Services\Dokumen;

use App\Exports\RekapitulasiExport;
use App\Models\Mahasiswa;
use App\Services\RekapitulasiService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;
use ZipArchive;

/**
 * Single registry of the per-mahasiswa documents admins can download, so
 * the slide-over panel and the "download all" zip always list the same set.
 * A new form becomes available by adding its route in urlFormulir() and its
 * file in buatZip().
 */
class DokumenMahasiswaService
{
    public const FORMULIR = [
        1 => 'Rincian Data Peserta / Calon Peserta',
        2 => 'Mata Kuliah yang Direkognisi',
        3 => 'Daftar Pelatihan dan Pengalaman Kerja',
        4 => 'Daftar Riwayat Hidup',
        5 => 'Asesmen Mandiri',
        6 => 'Asesmen Transfer SKS dari Pendidikan Formal',
        7 => 'Asesmen Transfer SKS dari Pendidikan Non-Formal',
        8 => 'Rekapitulasi Asesmen Perolehan SKS',
        9 => 'Rekapitulasi Hasil Asesmen untuk Program Studi',
    ];

    public function __construct(
        private Form1Generator $form1,
        private RekapitulasiService $rekap,
    ) {}

    /** @return list<array{nomor: int, judul: string, url: ?string, catatan: ?string}> */
    public function daftarFormulir(Mahasiswa $mahasiswa): array
    {
        $mahasiswa->loadMissing('biodata');

        return collect(self::FORMULIR)->map(function (string $judul, int $nomor) use ($mahasiswa) {
            $url = $this->urlFormulir($nomor, $mahasiswa);

            return [
                'nomor' => $nomor,
                'judul' => $judul,
                'url' => $url,
                'catatan' => match (true) {
                    $url === null => 'Belum tersedia di sistem',
                    $nomor === 1 && ! $mahasiswa->biodata => 'Biodata belum diisi, isian akan kosong',
                    default => null,
                },
            ];
        })->values()->all();
    }

    private function urlFormulir(int $nomor, Mahasiswa $mahasiswa): ?string
    {
        return match ($nomor) {
            1 => route('admin-prodi.mahasiswa.form1', $mahasiswa),
            default => null,
        };
    }

    public function namaFileZip(Mahasiswa $mahasiswa): string
    {
        return 'Dokumen-RPL-'.Str::slug($mahasiswa->user->name).'-'.$mahasiswa->nim.'.zip';
    }

    /** Returns the path of a temporary zip; the caller is responsible for deleting it. */
    public function buatZip(Mahasiswa $mahasiswa): string
    {
        $dir = storage_path('app/tmp');
        File::ensureDirectoryExists($dir);

        $zipPath = $dir.'/'.Str::uuid().'.zip';
        $form1Path = $dir.'/'.Str::uuid().'.docx';

        $this->form1->simpan($mahasiswa, $form1Path);

        $dataRekap = $this->rekap->untuk($mahasiswa);
        $rekapPdf = Pdf::loadView('pdf.rekapitulasi', $dataRekap)->setPaper('a4', 'landscape')->output();
        $rekapXlsx = Excel::raw(new RekapitulasiExport($mahasiswa, $this->rekap), ExcelWriter::XLSX);

        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFile($form1Path, $this->form1->namaFile($mahasiswa));
        $zip->addFromString($this->rekap->namaFile($mahasiswa, 'pdf'), $rekapPdf);
        $zip->addFromString($this->rekap->namaFile($mahasiswa, 'xlsx'), $rekapXlsx);
        $zip->close();

        // ZipArchive only reads addFile() sources on close(), so this must come after it.
        File::delete($form1Path);

        return $zipPath;
    }
}
