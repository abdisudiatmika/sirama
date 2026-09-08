<?php

namespace App\Services;

use App\Models\Mahasiswa;

/**
 * Builds the §6.5 recap rows for one mahasiswa — shared by the Livewire
 * page, the PDF export, and the Excel export, so the three can never
 * silently drift apart on what a row or the average actually shows.
 */
class RekapitulasiService
{
    public function __construct(private PenilaianService $penilaianService) {}

    public function untuk(Mahasiswa $mahasiswa): array
    {
        $mahasiswa->loadMissing([
            'user', 'prodi', 'skema.matakuliah' => fn ($q) => $q->orderBy('semester')->orderBy('nama'),
            'skema.matakuliah.cpmk',
            'biodata', 'pendidikanTerakhir',
            'penugasanAsesor.asesor.user',
            'pengajuanMk.penilaian',
            'hasilPleno',
        ]);

        $asesorUrutan = $mahasiswa->penugasanAsesor->sortBy('urutan')->values();

        $baris = $mahasiswa->skema?->matakuliah->map(function ($mk) use ($mahasiswa, $asesorUrutan) {
            $pengajuan = $mahasiswa->pengajuanMk->firstWhere('matakuliah_id', $mk->id);

            $nilaiPerAsesor = $asesorUrutan->map(function ($penugasan) use ($pengajuan) {
                if (! $pengajuan) {
                    return null;
                }

                return $pengajuan->penilaian->firstWhere('asesor_id', $penugasan->asesor_id)?->nilai_akhir;
            });

            $rataRata = $pengajuan
                ? $this->penilaianService->rataRataAsesmen($pengajuan)
                : ['rata_rata' => null, 'jumlah_penilai' => 0];

            $hasilPleno = $mahasiswa->hasilPleno->firstWhere('matakuliah_id', $mk->id);

            $usulan = null;
            if ($rataRata['rata_rata'] !== null) {
                $usulan = $rataRata['rata_rata'] >= $mk->skor_minimum ? 'Direkognisi' : 'Tidak Direkognisi';
            }

            return (object) [
                'matakuliah' => $mk,
                'pengajuan' => $pengajuan,
                'nilai_per_asesor' => $nilaiPerAsesor,
                'rata_rata' => $rataRata['rata_rata'],
                'jumlah_penilai' => $rataRata['jumlah_penilai'],
                'total_asesor' => $asesorUrutan->count(),
                'usulan' => $usulan,
                'status' => $hasilPleno?->status,
            ];
        }) ?? collect();

        $jenjangLabel = '—';
        $pendidikan = $mahasiswa->pendidikanTerakhir;
        if ($pendidikan) {
            $institusi = filled($pendidikan->nama_pt) ? $pendidikan->nama_pt : $pendidikan->nama_sekolah;
            $jenjangLabel = trim(($pendidikan->program ? $pendidikan->program.' ' : '').$institusi);
        }

        return [
            'mahasiswa' => $mahasiswa,
            'asesorUrutan' => $asesorUrutan,
            'baris' => $baris,
            'jenjangLabel' => $jenjangLabel,
        ];
    }
}
