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
 * the slide-over panel, single downloads and the "download all" zip always
 * agree. A new form becomes available by adding its generator to GENERATOR.
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

    /** @var array<int, class-string<FormulirWord>> */
    private const GENERATOR = [
        1 => Form1Generator::class,
        2 => Form2Generator::class,
    ];

    public function __construct(private RekapitulasiService $rekap) {}

    public function generator(int $nomor): ?FormulirWord
    {
        return isset(self::GENERATOR[$nomor]) ? app(self::GENERATOR[$nomor]) : null;
    }

    /** @return list<array{nomor: int, judul: string, url: ?string, catatan: ?string}> */
    public function daftarFormulir(Mahasiswa $mahasiswa): array
    {
        $mahasiswa->loadMissing(['biodata', 'skema']);

        return collect(self::FORMULIR)->map(function (string $judul, int $nomor) use ($mahasiswa) {
            $tersedia = isset(self::GENERATOR[$nomor]);

            return [
                'nomor' => $nomor,
                'judul' => $judul,
                'url' => $tersedia ? route('admin-prodi.mahasiswa.formulir', [$mahasiswa, $nomor]) : null,
                'catatan' => match (true) {
                    ! $tersedia => 'Belum tersedia di sistem',
                    $nomor === 1 && ! $mahasiswa->biodata => 'Biodata belum diisi, isian akan kosong',
                    $nomor === 2 && ! $mahasiswa->skema => 'Belum ada skema, memakai semua mata kuliah prodi',
                    default => null,
                },
            ];
        })->values()->all();
    }

    /** Writes a form to a temporary file and returns its path; the caller deletes it. */
    public function simpanFormulir(FormulirWord $generator, Mahasiswa $mahasiswa): string
    {
        $path = $this->pathSementara('docx');
        $generator->simpan($mahasiswa, $path);

        return $path;
    }

    public function namaFileZip(Mahasiswa $mahasiswa): string
    {
        return 'Dokumen-RPL-'.Str::slug($mahasiswa->user->name).'-'.$mahasiswa->nim.'.zip';
    }

    /** Returns the path of a temporary zip; the caller is responsible for deleting it. */
    public function buatZip(Mahasiswa $mahasiswa): string
    {
        $zipPath = $this->pathSementara('zip');
        $sementara = [];

        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach (array_keys(self::GENERATOR) as $nomor) {
            $generator = $this->generator($nomor);
            $sementara[] = $path = $this->simpanFormulir($generator, $mahasiswa);
            $zip->addFile($path, $generator->namaFile($mahasiswa));
        }

        $rekapPdf = Pdf::loadView('pdf.rekapitulasi', $this->rekap->untuk($mahasiswa))->setPaper('a4', 'landscape')->output();
        $zip->addFromString($this->rekap->namaFile($mahasiswa, 'pdf'), $rekapPdf);
        $zip->addFromString(
            $this->rekap->namaFile($mahasiswa, 'xlsx'),
            Excel::raw(new RekapitulasiExport($mahasiswa, $this->rekap), ExcelWriter::XLSX),
        );

        $zip->close();

        // ZipArchive only reads addFile() sources on close(), so these must be deleted after it.
        File::delete($sementara);

        return $zipPath;
    }

    private function pathSementara(string $ekstensi): string
    {
        $dir = storage_path('app/tmp');
        File::ensureDirectoryExists($dir);

        return $dir.'/'.Str::uuid().'.'.$ekstensi;
    }
}
