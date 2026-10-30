<?php

namespace App\Http\Controllers\AdminProdi;

use App\Http\Controllers\Controller;
use App\Models\Mahasiswa;
use App\Services\RekapitulasiService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class RekapitulasiPdfController extends Controller
{
    public function __invoke(Mahasiswa $mahasiswa, RekapitulasiService $service)
    {
        $prodiIds = Auth::user()->prodiDikelola()->pluck('prodi.id');
        abort_unless($prodiIds->contains($mahasiswa->prodi_id), 403);

        $data = $service->untuk($mahasiswa);

        $pdf = Pdf::loadView('pdf.rekapitulasi', $data)->setPaper('a4', 'landscape');

        return $pdf->download($service->namaFile($mahasiswa, 'pdf'));
    }
}
