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
        $asesor = Auth::user()->asesor()
            ->with(['penugasan.mahasiswa.user', 'penugasan.mahasiswa.prodi', 'penugasan.mahasiswa.pengajuanMk.penilaian'])
            ->first();

        $daftar = $asesor?->penugasan
            ->filter(fn ($p) => in_array($p->mahasiswa->status_berkas, ['diajukan', 'sedang_dinilai', 'selesai_dinilai']))
            ->map(function ($penugasan) use ($asesor) {
                $pengajuan = $penugasan->mahasiswa->pengajuanMk;
                $total = $pengajuan->count();
                $selesai = $pengajuan->filter(fn ($p) => $p->selesaiDinilaiOleh($asesor->id))->count();

                return (object) [
                    'mahasiswa' => $penugasan->mahasiswa,
                    'total' => $total,
                    'selesai' => $selesai,
                ];
            });

        return view('livewire.asesor.dashboard', [
            'asesor' => $asesor,
            'daftar' => $daftar ?? collect(),
        ]);
    }
}
