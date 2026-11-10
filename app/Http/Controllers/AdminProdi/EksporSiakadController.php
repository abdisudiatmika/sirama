<?php

namespace App\Http\Controllers\AdminProdi;

use App\Exports\RekognisiSiakadExport;
use App\Http\Controllers\Controller;
use App\Models\Mahasiswa;
use App\Models\Prodi;
use App\Models\Skema;
use App\Services\PenilaianService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class EksporSiakadController extends Controller
{
    public function __invoke(Request $request, PenilaianService $penilaian)
    {
        $prodi = Prodi::findOrFail($request->integer('prodi'));
        abort_unless(Auth::user()->prodiDikelola()->whereKey($prodi->id)->exists(), 403);

        $skema = $request->filled('skema')
            ? Skema::where('prodi_id', $prodi->id)->findOrFail($request->integer('skema'))
            : null;

        $namaFile = 'MK-RPL-SIAKAD-'.$prodi->kode.($skema ? '-'.Str::slug($skema->nama) : '').'-'.now()->format('Ymd').'.xlsx';

        return Excel::download(new RekognisiSiakadExport($prodi->id, $skema?->id, $penilaian), $namaFile);
    }

    public function mahasiswa(Mahasiswa $mahasiswa, PenilaianService $penilaian)
    {
        abort_unless(Auth::user()->prodiDikelola()->whereKey($mahasiswa->prodi_id)->exists(), 403);

        $namaFile = 'MK-RPL-SIAKAD-'.Str::slug($mahasiswa->user->name).'-'.$mahasiswa->nim.'.xlsx';

        return Excel::download(new RekognisiSiakadExport($mahasiswa->prodi_id, null, $penilaian, $mahasiswa->id), $namaFile);
    }
}
