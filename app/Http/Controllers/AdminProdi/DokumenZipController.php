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

        // Building ~17 documents (incl. four dompdf/PhpSpreadsheet recaps) peaks near the
        // common 128M hosting default, so give this one request more headroom.
        ini_set('memory_limit', '256M');

        return response()
            ->download($dokumen->buatZip($mahasiswa), $dokumen->namaFileZip($mahasiswa))
            ->deleteFileAfterSend();
    }
}
