<?php

namespace App\Livewire\AdminProdi;

use App\Livewire\Concerns\InteractsWithProdiContext;
use App\Models\Asesor;
use App\Models\Mahasiswa;
use App\Models\Skema;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Monitoring Penilaian')]
class MonitoringPenilaian extends Component
{
    use InteractsWithProdiContext, WithPagination;

    public string $search = '';

    public ?int $filterSkemaId = null;

    public ?int $filterTahunMasuk = null;

    public ?int $filterAsesorId = null;

    public string $filterStatus = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterSkemaId(): void
    {
        $this->resetPage();
    }

    public function updatingFilterTahunMasuk(): void
    {
        $this->resetPage();
    }

    public function updatingFilterAsesorId(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $mahasiswaList = Mahasiswa::where('prodi_id', $this->prodiId)
            ->with(['user', 'skema', 'penugasanAsesor.asesor.user', 'pengajuanMk.bukti', 'pengajuanMk.penilaian'])
            ->when($this->search, fn ($q) => $q->where('nim', 'like', "%{$this->search}%")->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$this->search}%")))
            ->when($this->filterSkemaId, fn ($q) => $q->where('skema_id', $this->filterSkemaId))
            ->when($this->filterTahunMasuk, fn ($q) => $q->where('tahun_masuk', $this->filterTahunMasuk))
            ->when($this->filterAsesorId, fn ($q) => $q->whereHas('penugasanAsesor', fn ($pq) => $pq->where('asesor_id', $this->filterAsesorId)))
            ->when($this->filterStatus, fn ($q) => $q->where('status_berkas', $this->filterStatus))
            ->orderByDesc('tahun_masuk')
            ->orderBy('nim')
            ->paginate(10);

        $mahasiswaList->getCollection()->transform(function ($mhs) {
            $mhs->progresAsesor = $mhs->penugasanAsesor->map(function ($penugasan) use ($mhs) {
                $total = $mhs->pengajuanMk->count();
                $selesai = $mhs->pengajuanMk->filter(fn ($p) => $p->selesaiDinilaiOleh($penugasan->asesor_id))->count();

                return (object) [
                    'urutan' => $penugasan->urutan,
                    'nama' => $penugasan->asesor->user->name,
                    'selesai' => $selesai,
                    'total' => $total,
                ];
            });

            return $mhs;
        });

        return view('livewire.admin-prodi.monitoring-penilaian', [
            'mahasiswaList' => $mahasiswaList,
            'skemaOptions' => Skema::where('prodi_id', $this->prodiId)->orderBy('nama')->get(),
            'asesorOptions' => Asesor::whereHas('penugasan.mahasiswa', fn ($q) => $q->where('prodi_id', $this->prodiId))
                ->with('user')->get()->unique('id')->sortBy(fn ($a) => $a->user->name),
            'tahunMasukOptions' => Mahasiswa::where('prodi_id', $this->prodiId)->distinct()->orderByDesc('tahun_masuk')->pluck('tahun_masuk'),
            'prodiOptions' => $this->prodiOptions(),
        ]);
    }
}
