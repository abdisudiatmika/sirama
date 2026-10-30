<?php

namespace App\Http\Controllers\AdminProdi;

use App\Http\Controllers\Controller;
use App\Models\Mahasiswa;
use App\Services\Dokumen\DokumenMahasiswaService;
use Illuminate\Support\Facades\Auth;

class FormulirWordController extends Controller
{
    public function __invoke(Mahasiswa $mahasiswa, int $nomor, DokumenMahasiswaService $dokumen)
    {
        $prodiIds = Auth::user()->prodiDikelola()->pluck('prodi.id');
        abort_unless($prodiIds->contains($mahasiswa->prodi_id), 403);

        abort_unless($dokumen->bisaDiunduhAdmin($nomor), 404);
        $generator = $dokumen->generator($nomor);

        return response()
            ->download($dokumen->simpanFormulir($generator, $mahasiswa), $generator->namaFile($mahasiswa))
            ->deleteFileAfterSend();
    }
}
