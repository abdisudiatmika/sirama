<?php

namespace App\Http\Controllers\AdminProdi;

use App\Http\Controllers\Controller;
use App\Models\Mahasiswa;
use App\Services\Dokumen\DokumenMahasiswaService;
use Illuminate\Support\Facades\Auth;

class DokumenZipController extends Controller
{
    public function __invoke(Mahasiswa $mahasiswa, DokumenMahasiswaService $dokumen)
    {
        $prodiIds = Auth::user()->prodiDikelola()->pluck('prodi.id');
        abort_unless($prodiIds->contains($mahasiswa->prodi_id), 403);

        return response()
            ->download($dokumen->buatZip($mahasiswa), $dokumen->namaFileZip($mahasiswa))
            ->deleteFileAfterSend();
    }
}
