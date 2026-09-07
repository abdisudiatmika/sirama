<?php

namespace App\Livewire\SuperAdmin;

use App\Models\Prodi;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Program Studi')]
class ProdiManager extends Component
{
    use WithPagination;

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $kode = '';

    public string $nama = '';

    public string $jenjang = 'D4';

    public bool $is_aktif = true;

    public string $search = '';

    protected function rules(): array
    {
        return [
            'kode' => ['required', 'string', 'max:20', 'unique:prodi,kode,'.$this->editingId],
            'nama' => ['required', 'string', 'max:150'],
            'jenjang' => ['required', 'in:D3,D4,S1'],
            'is_aktif' => ['boolean'],
        ];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function tambah(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $prodi = Prodi::findOrFail($id);
        $this->editingId = $prodi->id;
        $this->kode = $prodi->kode;
        $this->nama = $prodi->nama;
        $this->jenjang = $prodi->jenjang;
        $this->is_aktif = $prodi->is_aktif;
        $this->showModal = true;
    }

    public function simpan(): void
    {
        $data = $this->validate();

        Prodi::updateOrCreate(['id' => $this->editingId], $data);

        session()->flash('success', $this->editingId ? 'Program studi berhasil diperbarui.' : 'Program studi berhasil ditambahkan.');
        $this->closeModal();
    }

    public function toggleAktif(int $id): void
    {
        $prodi = Prodi::findOrFail($id);
        $prodi->update(['is_aktif' => ! $prodi->is_aktif]);
        session()->flash('success', 'Status program studi diperbarui.');
    }

    public function hapus(int $id): void
    {
        $prodi = Prodi::withCount(['matakuliah', 'skema', 'mahasiswa', 'admins'])->findOrFail($id);

        if ($prodi->matakuliah_count || $prodi->skema_count || $prodi->mahasiswa_count || $prodi->admins_count) {
            session()->flash('error', 'Program studi tidak dapat dihapus karena masih memiliki data terkait. Non-aktifkan saja.');

            return;
        }

        $prodi->delete();
        session()->flash('success', 'Program studi berhasil dihapus.');
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'kode', 'nama']);
        $this->jenjang = 'D4';
        $this->is_aktif = true;
        $this->resetErrorBag();
    }

    public function render()
    {
        $prodiList = Prodi::withCount('mahasiswa')
            ->when($this->search, fn ($q) => $q->where('nama', 'like', "%{$this->search}%")->orWhere('kode', 'like', "%{$this->search}%"))
            ->orderBy('nama')
            ->paginate(10);

        return view('livewire.super-admin.prodi-manager', [
            'prodiList' => $prodiList,
        ]);
    }
}
