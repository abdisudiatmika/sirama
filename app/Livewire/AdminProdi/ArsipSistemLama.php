<?php

namespace App\Livewire\AdminProdi;

use App\Livewire\Concerns\InteractsWithProdiContext;
use App\Models\LampiranLama;
use App\Models\Mahasiswa;
use App\Services\LampiranLamaService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Arsip Sistem Lama')]
class ArsipSistemLama extends Component
{
    use InteractsWithProdiContext, WithPagination;

    public string $search = '';

    public ?int $dibuka = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function toggle(int $mahasiswaId): void
    {
        $this->dibuka = $this->dibuka === $mahasiswaId ? null : $mahasiswaId;
    }

    public function render()
    {
        $daftar = Mahasiswa::where('prodi_id', $this->prodiId)
            ->whereHas('lampiranLama')
            ->with(['user', 'lampiranLama' => fn ($q) => $q->orderBy('label')->orderBy('id')])
            ->when($this->search, fn ($q) => $q->where(fn ($q) => $q
                ->whereHas('user', fn ($u) => $u->where('name', 'like', "%{$this->search}%"))
                ->orWhere('nim', 'like', "%{$this->search}%")))
            ->orderBy('nim')
            ->paginate(15);

        $ringkasan = LampiranLama::whereHas('mahasiswa', fn ($q) => $q->where('prodi_id', $this->prodiId))
            ->selectRaw('count(distinct mahasiswa_id) as mahasiswa, count(*) as file, coalesce(sum(ukuran), 0) as ukuran')
            ->first();

        return view('livewire.admin-prodi.arsip-sistem-lama', [
            'daftar' => $daftar,
            'ringkasan' => $ringkasan,
            'prodiOptions' => $this->prodiOptions(),
            'service' => app(LampiranLamaService::class),
        ]);
    }
}
