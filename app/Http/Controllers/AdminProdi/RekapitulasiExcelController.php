<?php

namespace App\Http\Controllers\AdminProdi;

use App\Exports\RekapitulasiExport;
use App\Http\Controllers\Controller;
use App\Models\Mahasiswa;
use App\Services\RekapitulasiService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class RekapitulasiExcelController extends Controller
{
    public function __invoke(Mahasiswa $mahasiswa, RekapitulasiService $service)
    {
        $prodiIds = Auth::user()->prodiDikelola()->pluck('prodi.id');
        abort_unless($prodiIds->contains($mahasiswa->prodi_id), 403);

        $namaFile = 'Rekap-'.Str::slug($mahasiswa->user->name).'-'.$mahasiswa->nim.'.xlsx';

        return Excel::download(new RekapitulasiExport($mahasiswa, $service), $namaFile);
    }
}
