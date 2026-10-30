<?php

namespace App\Http\Controllers\AdminProdi;

use App\Http\Controllers\Controller;
use App\Models\Mahasiswa;
use App\Services\RekapitulasiService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RekapitulasiPdfController extends Controller
{
    public function __invoke(Request $request, Mahasiswa $mahasiswa, RekapitulasiService $service)
    {
        $prodiIds = Auth::user()->prodiDikelola()->pluck('prodi.id');
        abort_unless($prodiIds->contains($mahasiswa->prodi_id), 403);

        $hanyaDiajukan = $request->boolean('diajukan');
        $data = $service->untuk($mahasiswa, $hanyaDiajukan);

        $pdf = Pdf::loadView('pdf.rekapitulasi', $data)->setPaper('a4', 'landscape');

        return $pdf->download($service->namaFile($mahasiswa, 'pdf', $hanyaDiajukan));
    }
}
