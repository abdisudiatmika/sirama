<?php

namespace App\Services;

use App\Models\Mahasiswa;

/**
 * Moves a file along as scores come in (§9): the first saved score turns
 * "diajukan" into "sedang_dinilai", and once every assigned assessor has
 * finished every submitted course it becomes "selesai_dinilai". Shared by the
 * assessor form and the admin's Excel import so both follow the same rule.
 */
class StatusBerkasService
{
    public function perbarui(Mahasiswa $mahasiswa): void
    {
        $mahasiswa->load(['pengajuanMk.bukti', 'pengajuanMk.penilaian', 'penugasanAsesor']);

        if ($mahasiswa->status_berkas === 'diajukan') {
            $mahasiswa->update(['status_berkas' => 'sedang_dinilai']);
        }

        $asesorIds = $mahasiswa->penugasanAsesor->pluck('asesor_id');
        $semuaSelesai = $asesorIds->isNotEmpty() && $asesorIds->every(
            fn ($asesorId) => $mahasiswa->pengajuanMk->every(fn ($p) => $p->selesaiDinilaiOleh($asesorId))
        );

        if ($semuaSelesai && $mahasiswa->status_berkas !== 'selesai_dinilai') {
            $mahasiswa->update(['status_berkas' => 'selesai_dinilai']);
        }
    }
}
