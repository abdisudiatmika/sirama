<?php

namespace App\Livewire\AdminProdi;

use App\Livewire\Concerns\InteractsWithProdiContext;
use App\Livewire\Concerns\WithBarisPerHalaman;
use App\Models\Cpmk;
use App\Models\Matakuliah;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Matakuliah & CPMK')]
class MatakuliahManager extends Component
{
    use InteractsWithProdiContext, WithBarisPerHalaman, WithPagination;

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $kode = '';

    public string $nama = '';

    public int $sks = 2;

    public ?int $semester = null;

    public int $skor_minimum = 66;

    public bool $dapat_direkognisi = true;

    public bool $is_aktif = true;

    public string $search = '';

    // CPMK panel
    public bool $showCpmkModal = false;

    public ?int $cpmkMatakuliahId = null;

    public string $cpmkKode = '';

    public string $cpmkDeskripsi = '';

    public ?int $editingCpmkId = null;

    protected function rules(): array
    {
        return [
            'kode' => ['required', 'string', 'max:30'],
            'nama' => ['required', 'string', 'max:150'],
            'sks' => ['required', 'integer', 'min:1', 'max:24'],
            'semester' => ['nullable', 'integer', 'min:1', 'max:14'],
            'skor_minimum' => ['required', 'integer', 'min:0', 'max:100'],
            'dapat_direkognisi' => ['boolean'],
            'is_aktif' => ['boolean'],
        ];
    }

    public function tambah(): void
    {
        $this->resetForm();
        $this->skor_minimum = (int) config('rpl.skor_minimum_default');
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $mk = Matakuliah::findOrFail($id);
        $this->assertProdiAuthorized($mk->prodi_id);

        $this->editingId = $mk->id;
        $this->kode = $mk->kode;
        $this->nama = $mk->nama;
        $this->sks = $mk->sks;
        $this->semester = $mk->semester;
        $this->skor_minimum = $mk->skor_minimum;
        $this->dapat_direkognisi = $mk->dapat_direkognisi;
        $this->is_aktif = $mk->is_aktif;
        $this->showModal = true;
    }

    public function simpan(): void
    {
        $data = $this->validate();
        $data['prodi_id'] = $this->prodiId;

        $exists = Matakuliah::where('prodi_id', $this->prodiId)
            ->where('kode', $data['kode'])
            ->when($this->editingId, fn ($q) => $q->where('id', '!=', $this->editingId))
            ->exists();

        if ($exists) {
            $this->addError('kode', 'Kode matakuliah sudah dipakai pada prodi ini.');

            return;
        }

        Matakuliah::updateOrCreate(['id' => $this->editingId], $data);

        session()->flash('success', $this->editingId ? 'Matakuliah berhasil diperbarui.' : 'Matakuliah berhasil ditambahkan.');
        $this->closeModal();
    }

    public function toggleAktif(int $id): void
    {
        $mk = Matakuliah::findOrFail($id);
        $this->assertProdiAuthorized($mk->prodi_id);
        $mk->update(['is_aktif' => ! $mk->is_aktif]);
        session()->flash('success', 'Status matakuliah diperbarui.');
    }

    public function hapus(int $id): void
    {
        $mk = Matakuliah::withCount(['cpmk'])->findOrFail($id);
        $this->assertProdiAuthorized($mk->prodi_id);

        $dipakaiSkema = $mk->skema()->exists();

        if ($dipakaiSkema) {
            session()->flash('error', 'Matakuliah tidak dapat dihapus karena sudah dipakai pada skema. Non-aktifkan saja.');

            return;
        }

        $mk->delete();
        session()->flash('success', 'Matakuliah berhasil dihapus.');
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'kode', 'nama', 'semester']);
        $this->sks = 2;
        $this->dapat_direkognisi = true;
        $this->is_aktif = true;
        $this->resetErrorBag();
    }

    // --- CPMK ---

    public function kelolaCpmk(int $matakuliahId): void
    {
        $mk = Matakuliah::findOrFail($matakuliahId);
        $this->assertProdiAuthorized($mk->prodi_id);

        $this->cpmkMatakuliahId = $matakuliahId;
        $this->resetCpmkForm();
        $this->showCpmkModal = true;
    }

    public function tambahCpmk(): void
    {
        $this->validate([
            'cpmkKode' => ['required', 'string', 'max:20'],
            'cpmkDeskripsi' => ['required', 'string'],
        ]);

        $urutan = Cpmk::where('matakuliah_id', $this->cpmkMatakuliahId)->max('urutan') + 1;

        if ($this->editingCpmkId) {
            Cpmk::where('id', $this->editingCpmkId)->update([
                'kode' => $this->cpmkKode,
                'deskripsi' => $this->cpmkDeskripsi,
            ]);
        } else {
            Cpmk::create([
                'matakuliah_id' => $this->cpmkMatakuliahId,
                'kode' => $this->cpmkKode,
                'deskripsi' => $this->cpmkDeskripsi,
                'urutan' => $urutan,
            ]);
        }

        $this->resetCpmkForm();
    }

    public function editCpmk(int $cpmkId): void
    {
        $cpmk = Cpmk::findOrFail($cpmkId);
        $this->editingCpmkId = $cpmk->id;
        $this->cpmkKode = $cpmk->kode;
        $this->cpmkDeskripsi = $cpmk->deskripsi;
    }

    public function hapusCpmk(int $cpmkId): void
    {
        Cpmk::where('id', $cpmkId)->delete();
    }

    public function batalEditCpmk(): void
    {
        $this->resetCpmkForm();
    }

    public function closeCpmkModal(): void
    {
        $this->showCpmkModal = false;
        $this->cpmkMatakuliahId = null;
        $this->resetCpmkForm();
    }

    private function resetCpmkForm(): void
    {
        $this->reset(['editingCpmkId', 'cpmkKode', 'cpmkDeskripsi']);
        $this->resetErrorBag();
    }

    public function render()
    {
        $matakuliahList = Matakuliah::where('prodi_id', $this->prodiId)
            ->withCount('cpmk')
            ->when($this->search, fn ($q) => $q->where('nama', 'like', "%{$this->search}%")->orWhere('kode', 'like', "%{$this->search}%"))
            ->orderBy('semester')
            ->orderBy('nama')
            ->paginate($this->barisPerHalaman());

        $cpmkList = $this->cpmkMatakuliahId
            ? Cpmk::where('matakuliah_id', $this->cpmkMatakuliahId)->orderBy('urutan')->get()
            : collect();

        return view('livewire.admin-prodi.matakuliah-manager', [
            'matakuliahList' => $matakuliahList,
            'cpmkList' => $cpmkList,
            'prodiOptions' => $this->prodiOptions(),
        ]);
    }
}
