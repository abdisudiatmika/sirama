<?php

namespace App\Http\Controllers\AdminProdi;

use App\Exports\RekapitulasiExport;
use App\Http\Controllers\Controller;
use App\Models\Mahasiswa;
use App\Services\RekapitulasiService;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class RekapitulasiExcelController extends Controller
{
    public function __invoke(Mahasiswa $mahasiswa, RekapitulasiService $service)
    {
        $prodiIds = Auth::user()->prodiDikelola()->pluck('prodi.id');
        abort_unless($prodiIds->contains($mahasiswa->prodi_id), 403);

        return Excel::download(new RekapitulasiExport($mahasiswa, $service), $service->namaFile($mahasiswa, 'xlsx'));
    }
}
