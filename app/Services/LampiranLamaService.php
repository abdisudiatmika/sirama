<?php

namespace App\Services;

use App\Models\LampiranLama;
use App\Models\Mahasiswa;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ZipArchive;

class LampiranLamaService
{
    /**
     * Absolute path of the stored file, or null when it is missing. Paths come
     * from the legacy database, so anything resolving outside the archive
     * folder (e.g. "../") is rejected rather than served.
     */
    public function pathAbsolut(LampiranLama $lampiran): ?string
    {
        $dasar = realpath(config('rpl.lampiran_lama_path'));
        $path = $dasar ? realpath($dasar.DIRECTORY_SEPARATOR.ltrim($lampiran->path, '/')) : false;

        if (! $path || ! str_starts_with($path, $dasar.DIRECTORY_SEPARATOR) || ! is_file($path)) {
            return null;
        }

        return $path;
    }

    public function namaFileZip(Mahasiswa $mahasiswa): string
    {
        return 'Lampiran-Sistem-Lama-'.Str::slug($mahasiswa->user->name).'-'.$mahasiswa->nim.'.zip';
    }

    /** Builds a temporary zip grouped by document type; the caller deletes it. */
    public function buatZip(Mahasiswa $mahasiswa): string
    {
        $dir = storage_path('app/tmp');
        File::ensureDirectoryExists($dir);
        $zipPath = $dir.'/'.Str::uuid().'.zip';

        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $dipakai = [];
        $hilang = [];

        foreach ($mahasiswa->lampiranLama()->orderBy('label')->orderBy('id')->get() as $lampiran) {
            $path = $this->pathAbsolut($lampiran);

            if (! $path) {
                $hilang[] = $lampiran->namaLabel().': '.$lampiran->nama_file;

                continue;
            }

            $nama = $this->namaUnik($lampiran->namaLabel().'/'.$this->namaAman($lampiran->nama_file), $dipakai);
            // Already-compressed PDFs/images/videos gain nothing from deflate; storing is far faster.
            $zip->addFile($path, $nama);
            $zip->setCompressionName($nama, ZipArchive::CM_STORE);
        }

        if ($hilang) {
            $zip->addFromString('File-tidak-ditemukan.txt', "File berikut tercatat di sistem lama tetapi tidak ada di server:\r\n\r\n".implode("\r\n", $hilang)."\r\n");
        }

        $zip->close();

        return $zipPath;
    }

    private function namaAman(string $nama): string
    {
        $nama = trim(str_replace(['/', '\\', "\0"], '-', $nama));

        return $nama !== '' ? $nama : 'lampiran';
    }

    /** Two uploads can share a file name; suffix duplicates so neither overwrites the other. */
    private function namaUnik(string $nama, array &$dipakai): string
    {
        $kandidat = $nama;
        $i = 2;

        while (isset($dipakai[mb_strtolower($kandidat)])) {
            $info = pathinfo($nama);
            $kandidat = $info['dirname'].'/'.$info['filename']." ({$i})".(isset($info['extension']) ? '.'.$info['extension'] : '');
            $i++;
        }

        $dipakai[mb_strtolower($kandidat)] = true;

        return $kandidat;
    }
}
