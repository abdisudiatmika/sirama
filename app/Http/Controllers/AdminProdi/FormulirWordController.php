<?php

namespace App\Http\Controllers\AdminProdi;

use App\Http\Controllers\Controller;
use App\Models\Mahasiswa;
use App\Services\Dokumen\DokumenMahasiswaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FormulirWordController extends Controller
{
    public function __invoke(Request $request, Mahasiswa $mahasiswa, int $nomor, DokumenMahasiswaService $dokumen)
    {
        $prodiIds = Auth::user()->prodiDikelola()->pluck('prodi.id');
        abort_unless($prodiIds->contains($mahasiswa->prodi_id), 403);
        abort_unless($dokumen->bisaDiunduhAdmin($nomor), 404);

        $generator = $dokumen->generator($nomor);
        $varian = $request->integer('varian') ?: null;
        abort_unless(collect($generator->daftarVarian($mahasiswa))->contains('varian', $varian), 404);

        $generator = $generator->denganVarian($varian);

        return response()
            ->download($dokumen->simpanFormulir($generator, $mahasiswa), $generator->namaFile($mahasiswa))
            ->deleteFileAfterSend();
    }
}
