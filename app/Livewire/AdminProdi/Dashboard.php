<?php

namespace App\Livewire\AdminProdi;

use App\Livewire\Concerns\InteractsWithProdiContext;
use App\Models\Mahasiswa;
use App\Models\Matakuliah;
use App\Models\Skema;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Dashboard')]
class Dashboard extends Component
{
    use InteractsWithProdiContext;

    public function render()
    {
        $stats = [
            'matakuliah' => Matakuliah::where('prodi_id', $this->prodiId)->count(),
            'skema' => Skema::where('prodi_id', $this->prodiId)->count(),
            'mahasiswa' => Mahasiswa::where('prodi_id', $this->prodiId)->count(),
        ];

        $statusBerkas = Mahasiswa::where('prodi_id', $this->prodiId)
            ->selectRaw('status_berkas, count(*) as total')
            ->groupBy('status_berkas')
            ->pluck('total', 'status_berkas');

        return view('livewire.admin-prodi.dashboard', [
            'stats' => $stats,
            'statusBerkas' => $statusBerkas,
            'prodiOptions' => $this->prodiOptions(),
        ]);
    }
}
