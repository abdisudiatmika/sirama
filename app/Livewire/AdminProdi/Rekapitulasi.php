<?php

namespace App\Livewire\AdminProdi;

use App\Models\HasilPleno;
use App\Models\Mahasiswa;
use App\Services\RekapitulasiService;
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

    public function render(RekapitulasiService $service)
    {
        $data = $service->untuk(Mahasiswa::findOrFail($this->mahasiswaId));

        return view('livewire.admin-prodi.rekapitulasi', $data);
    }
}
