<?php

namespace App\Livewire\Asesor;

use App\Services\TandaTanganAsesorService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('Dashboard')]
class Dashboard extends Component
{
    use WithFileUploads;

    public $fileTtd = null;

    public bool $ubahTtd = false;

    public function updatedFileTtd(): void
    {
        $this->validateOnly('fileTtd', ['fileTtd' => TandaTanganAsesorService::ATURAN], [], ['fileTtd' => 'file tanda tangan']);
    }

    public function simpanTtd(TandaTanganAsesorService $ttd): void
    {
        $this->validate(['fileTtd' => TandaTanganAsesorService::ATURAN], [], ['fileTtd' => 'file tanda tangan']);

        $asesor = Auth::user()->asesor;
        abort_unless($asesor, 403);
        $ttd->simpan($asesor, $this->fileTtd);

        $this->reset(['fileTtd', 'ubahTtd']);
        session()->flash('success', 'Tanda tangan Anda disimpan dan akan tercetak di formulir penilaian.');
    }

    public function render()
    {
        $asesor = Auth::user()->asesor()
            ->with(['penugasan.mahasiswa.user', 'penugasan.mahasiswa.prodi', 'penugasan.mahasiswa.pengajuanMk.penilaian'])
            ->first();

        $daftar = $asesor?->penugasan
            ->filter(fn ($p) => in_array($p->mahasiswa->status_berkas, ['diajukan', 'sedang_dinilai', 'selesai_dinilai']))
            ->map(function ($penugasan) use ($asesor) {
                $pengajuan = $penugasan->mahasiswa->pengajuanDirekognisi();
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
