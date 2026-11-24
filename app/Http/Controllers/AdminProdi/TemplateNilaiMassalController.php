<?php

namespace App\Http\Controllers\AdminProdi;

use App\Http\Controllers\Controller;
use App\Models\Prodi;
use App\Models\Skema;
use App\Services\NilaiAdminService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/** Downloads the bulk score template (Input Nilai menu) for the chosen filters, filled with current scores. */
class TemplateNilaiMassalController extends Controller
{
    public function __invoke(Request $request, NilaiAdminService $nilai)
    {
        $prodi = Prodi::findOrFail($request->integer('prodi'));
        abort_unless(Auth::user()->prodiDikelola()->whereKey($prodi->id)->exists(), 403);

        $skema = $request->filled('skema') ? Skema::where('prodi_id', $prodi->id)->findOrFail($request->integer('skema')) : null;
        $angkatan = $request->filled('angkatan') ? $request->integer('angkatan') : null;
        $status = $request->filled('status') ? (string) $request->string('status') : null;

        $mahasiswa = $nilai->mahasiswaMassal($prodi->id, $skema?->id, $angkatan, $status);
        $keterangan = collect([
            'Prodi: '.$prodi->nama,
            'Skema: '.($skema->nama ?? 'semua'),
            'Angkatan: '.($angkatan ?? 'semua'),
            'Status: '.($status ? str_replace('_', ' ', $status) : 'semua'),
            $mahasiswa->count().' mahasiswa',
            'diunduh '.now('Asia/Makassar')->format('d-m-Y H:i').' WITA',
        ])->implode(' · ');

        @set_time_limit(300);
        $book = $nilai->templateMassal($mahasiswa, $keterangan);
        $namaFile = 'Nilai-Asesor-Massal-'.$prodi->kode.($skema ? '-'.str($skema->nama)->slug() : '').'-'.now('Asia/Makassar')->format('Ymd-Hi').'.xlsx';

        return response()->streamDownload(
            fn () => (new Xlsx($book))->save('php://output'),
            $namaFile,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        )->setPrivate();
    }
}
