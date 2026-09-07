<?php

namespace App\Livewire\Asesor;

use App\Models\Mahasiswa;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Penilaian Mahasiswa')]
class DetailMahasiswa extends Component
{
    public int $mahasiswaId;

    public function mount(Mahasiswa $mahasiswa): void
    {
        $asesor = Auth::user()->asesor;
        abort_unless($asesor && $mahasiswa->penugasanAsesor()->where('asesor_id', $asesor->id)->exists(), 403);

        $this->mahasiswaId = $mahasiswa->id;
    }

    public function render()
    {
        $asesor = Auth::user()->asesor;
        $mahasiswa = Mahasiswa::with(['user', 'prodi', 'skema', 'pengajuanMk.matakuliah', 'pengajuanMk.bukti', 'pengajuanMk.penilaian'])
            ->findOrFail($this->mahasiswaId);

        $pengajuanList = $mahasiswa->pengajuanMk->map(function ($p) use ($asesor) {
            return (object) [
                'pengajuan' => $p,
                'jenis' => $p->jenisPenilaianTersedia(),
                'selesai' => $p->selesaiDinilaiOleh($asesor->id),
            ];
        });

        return view('livewire.asesor.detail-mahasiswa', [
            'mahasiswa' => $mahasiswa,
            'pengajuanList' => $pengajuanList,
        ]);
    }
}
