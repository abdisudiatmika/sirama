<?php

namespace App\Livewire\AdminProdi;

use App\Livewire\Concerns\InteractsWithProdiContext;
use App\Livewire\Concerns\WithBarisPerHalaman;
use App\Models\Asesor;
use App\Models\User;
use App\Services\TandaTanganAsesorService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Direktori Asesor')]
class AsesorDirectory extends Component
{
    use InteractsWithProdiContext, WithBarisPerHalaman, WithFileUploads, WithPagination;

    /** Assessor whose signature is being uploaded (modal open when set). */
    public ?int $ttdAsesorId = null;

    public $fileTtd = null;

    public bool $showModal = false;

    public string $name = '';

    public string $username = '';

    public string $email = '';

    public string $nidn = '';

    public string $bidang_keahlian = '';

    public string $no_hp = '';

    public string $search = '';

    public ?string $generatedPassword = null;

    public ?string $generatedFor = null;

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'username' => ['required', 'string', 'max:50', 'unique:users,username'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'nidn' => ['nullable', 'string', 'max:30', 'unique:asesor,nidn'],
            'bidang_keahlian' => ['nullable', 'string', 'max:150'],
            'no_hp' => ['nullable', 'string', 'max:20'],
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

    public function simpan(): void
    {
        $data = $this->validate();

        $password = Str::password(12);
        $user = User::create([
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => Hash::make($password),
        ]);
        $user->assignRole('asesor');

        Asesor::create([
            'user_id' => $user->id,
            'nidn' => $data['nidn'] ?: null,
            'bidang_keahlian' => $data['bidang_keahlian'] ?: null,
            'no_hp' => $data['no_hp'] ?: null,
            'is_aktif' => true,
        ]);

        $this->generatedPassword = $password;
        $this->generatedFor = $user->username;
        $this->showModal = false;
        $this->resetForm();
    }

    public function bukaTtd(int $asesorId): void
    {
        $this->ttdAsesorId = Asesor::findOrFail($asesorId)->id;
        $this->reset('fileTtd');
        $this->resetErrorBag();
    }

    public function tutupTtd(): void
    {
        $this->reset(['ttdAsesorId', 'fileTtd']);
        $this->resetErrorBag();
    }

    public function updatedFileTtd(): void
    {
        $this->validateOnly('fileTtd', ['fileTtd' => TandaTanganAsesorService::ATURAN], [], ['fileTtd' => 'file tanda tangan']);
    }

    public function simpanTtd(TandaTanganAsesorService $ttd): void
    {
        $this->validate(['fileTtd' => TandaTanganAsesorService::ATURAN], [], ['fileTtd' => 'file tanda tangan']);

        $asesor = Asesor::with('user')->findOrFail($this->ttdAsesorId);
        $ttd->simpan($asesor, $this->fileTtd);

        session()->flash('success', 'Tanda tangan '.$asesor->user->name.' disimpan.');
        $this->tutupTtd();
    }

    public function hapusTtd(TandaTanganAsesorService $ttd): void
    {
        $asesor = Asesor::with('user')->findOrFail($this->ttdAsesorId);
        $ttd->hapus($asesor);

        session()->flash('success', 'Tanda tangan '.$asesor->user->name.' dihapus.');
        $this->tutupTtd();
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function tutupInfoPassword(): void
    {
        $this->generatedPassword = null;
        $this->generatedFor = null;
    }

    private function resetForm(): void
    {
        $this->reset(['name', 'username', 'email', 'nidn', 'bidang_keahlian', 'no_hp']);
        $this->resetErrorBag();
    }

    public function render()
    {
        $asesorList = Asesor::join('users', 'users.id', '=', 'asesor.user_id')
            ->select('asesor.*')
            ->with('user')
            ->where('is_aktif', true)
            ->withCount('penugasan')
            // Grouped so a NIDN match never brings back inactive assessors.
            ->when($this->search, fn ($q) => $q->where(fn ($w) => $w
                ->whereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$this->search}%")->orWhere('username', 'like', "%{$this->search}%"))
                ->orWhere('nidn', 'like', "%{$this->search}%")))
            ->orderBy('users.name')
            ->paginate($this->barisPerHalaman());

        return view('livewire.admin-prodi.asesor-directory', [
            'asesorList' => $asesorList,
            'ttdAsesor' => $this->ttdAsesorId ? Asesor::with('user')->find($this->ttdAsesorId) : null,
            'prodiOptions' => $this->prodiOptions(),
        ]);
    }
}
