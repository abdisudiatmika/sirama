<?php

namespace App\Http\Controllers\AdminProdi;

use App\Http\Controllers\Controller;
use App\Models\Mahasiswa;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class Form1PdfController extends Controller
{
    public function __invoke(Mahasiswa $mahasiswa)
    {
        $prodiIds = Auth::user()->prodiDikelola()->pluck('prodi.id');
        abort_unless($prodiIds->contains($mahasiswa->prodi_id), 403);

        $mahasiswa->load(['user', 'prodi', 'biodata', 'pendidikanTerakhir']);

        $pdf = Pdf::loadView('pdf.form1', ['mahasiswa' => $mahasiswa])->setPaper('a4', 'portrait');

        $namaFile = 'Form1-'.Str::slug($mahasiswa->user->name).'-'.$mahasiswa->nim.'.pdf';

        return $pdf->download($namaFile);
    }
}
