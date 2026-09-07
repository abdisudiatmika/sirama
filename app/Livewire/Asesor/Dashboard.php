<?php

namespace App\Livewire\Asesor;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Dashboard')]
class Dashboard extends Component
{
    public function render()
    {
        $asesor = Auth::user()->asesor()->with('penugasan.mahasiswa.user', 'penugasan.mahasiswa.prodi')->first();

        return view('livewire.asesor.dashboard', [
            'asesor' => $asesor,
        ]);
    }
}
