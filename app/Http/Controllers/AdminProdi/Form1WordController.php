<?php

namespace App\Http\Controllers\AdminProdi;

use App\Http\Controllers\Controller;
use App\Models\Mahasiswa;
use App\Services\Dokumen\Form1Generator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class Form1WordController extends Controller
{
    public function __invoke(Mahasiswa $mahasiswa, Form1Generator $generator)
    {
        $prodiIds = Auth::user()->prodiDikelola()->pluck('prodi.id');
        abort_unless($prodiIds->contains($mahasiswa->prodi_id), 403);

        $dir = storage_path('app/tmp');
        File::ensureDirectoryExists($dir);
        $path = $dir.'/'.Str::uuid().'.docx';

        $generator->simpan($mahasiswa, $path);

        return response()->download($path, $generator->namaFile($mahasiswa))->deleteFileAfterSend();
    }
}
