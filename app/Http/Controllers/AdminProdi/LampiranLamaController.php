<?php

namespace App\Http\Controllers\AdminProdi;

use App\Http\Controllers\Controller;
use App\Models\LampiranLama;
use App\Models\Mahasiswa;
use App\Services\LampiranLamaService;
use Illuminate\Support\Facades\Auth;

class LampiranLamaController extends Controller
{
    /** Types safe to render in the browser; anything else from the legacy data is forced to download. */
    private const TAMPIL_LANGSUNG = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'];

    // download() marks responses public by default; these are personal documents, so callers add setPrivate().
    private const HEADER = ['Cache-Control' => 'no-store', 'X-Content-Type-Options' => 'nosniff'];

    public function __construct(private LampiranLamaService $service) {}

    public function file(LampiranLama $lampiran)
    {
        $this->izinkan($lampiran->mahasiswa);

        $path = $this->service->pathAbsolut($lampiran);
        abort_unless($path, 404, 'File tidak ditemukan di server.');

        $tampil = in_array($lampiran->mime_type, self::TAMPIL_LANGSUNG, true);

        return response()->download(
            $path,
            $lampiran->nama_file,
            self::HEADER + ['Content-Type' => $tampil ? $lampiran->mime_type : 'application/octet-stream'],
            $tampil ? 'inline' : 'attachment',
        )->setPrivate();
    }

    public function zip(Mahasiswa $mahasiswa)
    {
        $this->izinkan($mahasiswa);
        abort_unless($mahasiswa->lampiranLama()->exists(), 404);

        return response()
            ->download($this->service->buatZip($mahasiswa), $this->service->namaFileZip($mahasiswa), self::HEADER)
            ->deleteFileAfterSend()
            ->setPrivate();
    }

    private function izinkan(Mahasiswa $mahasiswa): void
    {
        $prodiIds = Auth::user()->prodiDikelola()->pluck('prodi.id');
        abort_unless($prodiIds->contains($mahasiswa->prodi_id), 403);
    }
}
