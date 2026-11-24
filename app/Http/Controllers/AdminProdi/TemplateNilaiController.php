<?php

namespace App\Http\Controllers\AdminProdi;

use App\Http\Controllers\Controller;
use App\Models\Mahasiswa;
use App\Services\NilaiAdminService;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/** Downloads one student's assessor-score template, already filled with the current scores. */
class TemplateNilaiController extends Controller
{
    public function __invoke(Mahasiswa $mahasiswa, NilaiAdminService $nilai)
    {
        abort_unless(Auth::user()->prodiDikelola()->whereKey($mahasiswa->prodi_id)->exists(), 403);

        $book = $nilai->template($mahasiswa);

        return response()->streamDownload(
            fn () => (new Xlsx($book))->save('php://output'),
            $nilai->namaFile($mahasiswa),
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        )->setPrivate();
    }
}
