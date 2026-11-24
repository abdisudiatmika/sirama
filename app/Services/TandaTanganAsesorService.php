<?php

namespace App\Services;

use App\Models\Asesor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Stores an assessor's signature image on the private disk and swaps out the
 * previous one. Shared by the admin's upload, the assessor's own upload and the
 * legacy import, so every path ends up in the same folder with the same rules.
 */
class TandaTanganAsesorService
{
    public const FOLDER = 'ttd-asesor';

    /** Validation rules for an uploaded signature. */
    public const ATURAN = ['required', 'image', 'mimes:png,jpg,jpeg', 'max:2048'];

    public function simpan(Asesor $asesor, UploadedFile|string $sumber): void
    {
        $ext = strtolower($sumber instanceof UploadedFile ? $sumber->extension() : pathinfo($sumber, PATHINFO_EXTENSION));
        $ext = $ext === 'jpeg' ? 'jpg' : $ext;
        $path = self::FOLDER.'/'.$asesor->id.'-'.Str::random(12).'.'.$ext;

        $sumber instanceof UploadedFile
            ? Storage::disk('local')->putFileAs(self::FOLDER, $sumber, basename($path))
            : Storage::disk('local')->put($path, file_get_contents($sumber));

        $lama = $asesor->tanda_tangan;
        $asesor->update(['tanda_tangan' => $path]);

        if ($lama && $lama !== $path) {
            Storage::disk('local')->delete($lama);
        }
    }

    public function hapus(Asesor $asesor): void
    {
        if ($asesor->tanda_tangan) {
            Storage::disk('local')->delete($asesor->tanda_tangan);
        }
        $asesor->update(['tanda_tangan' => null]);
    }
}
