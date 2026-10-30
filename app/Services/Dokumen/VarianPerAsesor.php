<?php

namespace App\Services\Dokumen;

use App\Models\Mahasiswa;
use App\Models\PenugasanAsesor;

/**
 * For forms each assessor fills and signs independently: one variant per
 * assigned assessor, where the variant number is the assessor's urutan.
 */
trait VarianPerAsesor
{
    public function daftarVarian(Mahasiswa $mahasiswa): array
    {
        $penugasan = $mahasiswa->penugasanAsesor()->orderBy('urutan')->get();

        if ($penugasan->isEmpty()) {
            return parent::daftarVarian($mahasiswa);
        }

        return $penugasan->map(fn (PenugasanAsesor $p) => ['varian' => $p->urutan, 'label' => 'Asesor '.$p->urutan])->all();
    }

    protected function labelVarian(): ?string
    {
        return $this->varian ? 'Asesor '.$this->varian : null;
    }

    protected function penugasanVarian(Mahasiswa $mahasiswa): ?PenugasanAsesor
    {
        return $this->varian
            ? $mahasiswa->penugasanAsesor()->with('asesor.user')->where('urutan', $this->varian)->first()
            : null;
    }
}
