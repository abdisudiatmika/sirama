<?php

namespace App\Livewire\SuperAdmin;

use App\Models\Asesor;
use App\Models\Mahasiswa;
use App\Models\Prodi;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Dashboard')]
class Dashboard extends Component
{
    public function render()
    {
        $stats = [
            'prodi' => Prodi::count(),
            'prodi_aktif' => Prodi::where('is_aktif', true)->count(),
            'mahasiswa' => Mahasiswa::count(),
            'asesor' => Asesor::count(),
            'admin_prodi' => User::role('admin_prodi')->count(),
        ];

        $prodiList = Prodi::withCount('mahasiswa')->orderBy('nama')->get();

        return view('livewire.super-admin.dashboard', [
            'stats' => $stats,
            'prodiList' => $prodiList,
        ]);
    }
}
