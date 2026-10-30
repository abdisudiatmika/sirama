<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Services\Dokumen\DokumenMahasiswaService;
use Illuminate\Support\Facades\Auth;

class TemplateFormulirController extends Controller
{
    public function __invoke(int $nomor, DokumenMahasiswaService $dokumen)
    {
        abort_unless(in_array($nomor, DokumenMahasiswaService::TEMPLATE_MAHASISWA, true), 404);

        $mahasiswa = Auth::user()->mahasiswa;
        abort_unless($mahasiswa, 403);

        $generator = $dokumen->generator($nomor);

        return response()
            ->download($dokumen->simpanFormulir($generator, $mahasiswa), $generator->namaFile($mahasiswa))
            ->deleteFileAfterSend();
    }
}
