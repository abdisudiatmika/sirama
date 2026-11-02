<?php

namespace App\Livewire\AdminProdi;

use App\Livewire\Concerns\InteractsWithProdiContext;
use App\Livewire\Concerns\WithBarisPerHalaman;
use App\Models\Matakuliah;
use App\Models\Skema;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Skema')]
class SkemaManager extends Component
{
    use InteractsWithProdiContext, WithBarisPerHalaman, WithPagination;

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $nama = '';

    public string $jenis = 'RPL';

    public string $tahun_akademik = '';

    public bool $is_aktif = true;

    /** @var array<int> */
    public array $selectedMatakuliah = [];

    protected function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:150'],
            'jenis' => ['required', 'in:RPL,Reguler,Lainnya'],
            'tahun_akademik' => ['required', 'string', 'max:20'],
            'is_aktif' => ['boolean'],
            'selectedMatakuliah' => ['array'],
        ];
    }

    public function tambah(): void
    {
        $this->resetForm();
        $this->tahun_akademik = now()->year.'/'.(now()->year + 1);
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $skema = Skema::with('matakuliah')->findOrFail($id);
        $this->assertProdiAuthorized($skema->prodi_id);

        $this->editingId = $skema->id;
        $this->nama = $skema->nama;
        $this->jenis = $skema->jenis;
        $this->tahun_akademik = $skema->tahun_akademik;
        $this->is_aktif = $skema->is_aktif;
        $this->selectedMatakuliah = $skema->matakuliah->pluck('id')->toArray();
        $this->showModal = true;
    }

    public function simpan(): void
    {
        $data = $this->validate();
        $matakuliahIds = $data['selectedMatakuliah'];
        unset($data['selectedMatakuliah']);
        $data['prodi_id'] = $this->prodiId;

        $skema = Skema::updateOrCreate(['id' => $this->editingId], $data);
        $skema->matakuliah()->sync($matakuliahIds);

        session()->flash('success', $this->editingId ? 'Skema berhasil diperbarui.' : 'Skema berhasil ditambahkan.');
        $this->closeModal();
    }

    public function toggleAktif(int $id): void
    {
        $skema = Skema::findOrFail($id);
        $this->assertProdiAuthorized($skema->prodi_id);
        $skema->update(['is_aktif' => ! $skema->is_aktif]);
        session()->flash('success', 'Status skema diperbarui.');
    }

    public function hapus(int $id): void
    {
        $skema = Skema::withCount('mahasiswa')->findOrFail($id);
        $this->assertProdiAuthorized($skema->prodi_id);

        if ($skema->mahasiswa_count > 0) {
            session()->flash('error', 'Skema tidak dapat dihapus karena masih diikuti mahasiswa. Non-aktifkan saja.');

            return;
        }

        $skema->matakuliah()->detach();
        $skema->delete();
        session()->flash('success', 'Skema berhasil dihapus.');
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'nama', 'tahun_akademik', 'selectedMatakuliah']);
        $this->jenis = 'RPL';
        $this->is_aktif = true;
        $this->resetErrorBag();
    }

    public function render()
    {
        $skemaList = Skema::where('prodi_id', $this->prodiId)
            ->withCount(['matakuliah', 'mahasiswa'])
            ->orderByDesc('tahun_akademik')
            ->orderBy('nama')
            ->paginate($this->barisPerHalaman());

        $matakuliahOptions = Matakuliah::where('prodi_id', $this->prodiId)
            ->where('is_aktif', true)
            ->orderBy('nama')
            ->get();

        return view('livewire.admin-prodi.skema-manager', [
            'skemaList' => $skemaList,
            'matakuliahOptions' => $matakuliahOptions,
            'prodiOptions' => $this->prodiOptions(),
        ]);
    }
}
