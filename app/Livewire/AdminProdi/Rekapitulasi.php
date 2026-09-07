<?php

namespace App\Livewire\AdminProdi;

use App\Models\HasilPleno;
use App\Models\Mahasiswa;
use App\Services\PenilaianService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Formulir Rekapitulasi')]
class Rekapitulasi extends Component
{
    public int $mahasiswaId;

    public function mount(Mahasiswa $mahasiswa): void
    {
        $prodiIds = Auth::user()->prodiDikelola()->pluck('prodi.id');
        abort_unless($prodiIds->contains($mahasiswa->prodi_id), 403);

        $this->mahasiswaId = $mahasiswa->id;
    }

    public function simpanStatus(int $matakuliahId, ?string $status): void
    {
        HasilPleno::updateOrCreate(
            ['mahasiswa_id' => $this->mahasiswaId, 'matakuliah_id' => $matakuliahId],
            [
                'status' => $status ?: null,
                'ditetapkan_oleh' => Auth::id(),
                'tanggal_pleno' => now(),
            ]
        );

        session()->flash('success', 'Status rekognisi disimpan.');
    }

    public function render(PenilaianService $service)
    {
        $mahasiswa = Mahasiswa::with([
            'user', 'prodi', 'skema.matakuliah' => fn ($q) => $q->orderBy('semester')->orderBy('nama'),
            'skema.matakuliah.cpmk',
            'biodata', 'pendidikanTerakhir',
            'penugasanAsesor.asesor.user',
            'pengajuanMk.penilaian',
            'hasilPleno',
        ])->findOrFail($this->mahasiswaId);

        $asesorUrutan = $mahasiswa->penugasanAsesor->sortBy('urutan')->values();

        $baris = $mahasiswa->skema?->matakuliah->map(function ($mk) use ($mahasiswa, $service, $asesorUrutan) {
            $pengajuan = $mahasiswa->pengajuanMk->firstWhere('matakuliah_id', $mk->id);

            $nilaiPerAsesor = $asesorUrutan->map(function ($penugasan) use ($pengajuan) {
                if (! $pengajuan) {
                    return null;
                }

                return $pengajuan->penilaian->firstWhere('asesor_id', $penugasan->asesor_id)?->nilai_akhir;
            });

            $rataRata = $pengajuan ? $service->rataRataAsesmen($pengajuan) : ['rata_rata' => null, 'jumlah_penilai' => 0];

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

        return view('livewire.admin-prodi.rekapitulasi', [
            'mahasiswa' => $mahasiswa,
            'asesorUrutan' => $asesorUrutan,
            'baris' => $baris,
        ]);
    }
}
