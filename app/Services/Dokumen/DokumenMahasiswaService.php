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
        10 => 'Rekapitulasi Hasil Asesmen untuk Program Studi (hasil asesor)',
    ];

    /** @var array<int, class-string<FormulirWord>> */
    private const GENERATOR = [
        1 => Form1Generator::class,
        2 => Form2Generator::class,
        3 => Form3Generator::class,
        4 => Form4Generator::class,
    ];

    /** Forms students download from the wizard to complete, sign and return as a Drive link. */
    public const TEMPLATE_MAHASISWA = [4];

    public function __construct(private RekapitulasiService $rekap) {}

    public function generator(int $nomor): ?FormulirWord
    {
        return isset(self::GENERATOR[$nomor]) ? app(self::GENERATOR[$nomor]) : null;
    }

    /** A form the student submitted themselves as a Google Drive link (the signed CV is Form 4). */
    public function linkMahasiswa(int $nomor, Mahasiswa $mahasiswa): ?string
    {
        return match ($nomor) {
            4 => $mahasiswa->dokumenCv?->url,
            default => null,
        };
    }

    /** @return list<array{nomor: int, judul: string, url: ?string, link: ?string, catatan: ?string}> */
    public function daftarFormulir(Mahasiswa $mahasiswa): array
    {
        $mahasiswa->loadMissing(['biodata', 'skema', 'dokumenCv', 'pernyataan']);

        return collect(self::FORMULIR)->map(function (string $judul, int $nomor) use ($mahasiswa) {
            $tersedia = isset(self::GENERATOR[$nomor]);
            $link = $this->linkMahasiswa($nomor, $mahasiswa);

            return [
                'nomor' => $nomor,
                'judul' => $judul,
                'url' => $tersedia ? route('admin-prodi.mahasiswa.formulir', [$mahasiswa, $nomor]) : null,
                'link' => $link,
                'catatan' => match (true) {
                    $nomor === 4 && ! $link => 'Mahasiswa belum mengirim link Daftar Riwayat Hidup',
                    ! $tersedia && ! $link => 'Belum tersedia di sistem',
                    $nomor === 1 && ! $mahasiswa->biodata => 'Biodata belum diisi, isian akan kosong',
                    $nomor === 2 && ! $mahasiswa->skema => 'Belum ada skema, memakai semua mata kuliah prodi',
                    $nomor === 3 && ! $mahasiswa->pernyataan?->disetujui => 'Mahasiswa belum mencentang persetujuan',
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

        $daftarLink = [];

        foreach (self::FORMULIR as $nomor => $judul) {
            if ($link = $this->linkMahasiswa($nomor, $mahasiswa)) {
                $daftarLink[] = "Form {$nomor} — {$judul}:\n{$link}";
            }

            if ($generator = $this->generator($nomor)) {
                $sementara[] = $path = $this->simpanFormulir($generator, $mahasiswa);
                $zip->addFile($path, $generator->namaFile($mahasiswa));
            }
        }

        $rekapPdf = Pdf::loadView('pdf.rekapitulasi', $this->rekap->untuk($mahasiswa))->setPaper('a4', 'landscape')->output();
        $zip->addFromString($this->rekap->namaFile($mahasiswa, 'pdf'), $rekapPdf);
        $zip->addFromString(
            $this->rekap->namaFile($mahasiswa, 'xlsx'),
            Excel::raw(new RekapitulasiExport($mahasiswa, $this->rekap), ExcelWriter::XLSX),
        );

        if ($daftarLink) {
            $zip->addFromString(
                'Link-Dokumen-Mahasiswa.txt',
                "Dokumen yang dikirim mahasiswa sebagai link (buka di browser):\r\n\r\n".str_replace("\n", "\r\n", implode("\n\n", $daftarLink))."\r\n",
            );
        }

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
