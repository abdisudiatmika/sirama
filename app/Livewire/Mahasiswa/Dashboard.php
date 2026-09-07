<?php

namespace App\Livewire\Mahasiswa;

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
        $mahasiswa = Auth::user()->mahasiswa()->with('skema', 'penugasanAsesor.asesor.user')->first();

        return view('livewire.mahasiswa.dashboard', [
            'mahasiswa' => $mahasiswa,
        ]);
    }
}
