<?php

namespace App\Livewire\SuperAdmin;

use App\Livewire\Concerns\WithBarisPerHalaman;
use App\Models\Asesor;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Direktori Asesor')]
class AsesorManager extends Component
{
    use WithBarisPerHalaman, WithPagination;

    public bool $showModal = false;

    public ?int $editingId = null;

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
            'username' => ['required', 'string', 'max:50', 'unique:users,username,'.$this->editingId],
            'email' => ['required', 'email', 'max:150', 'unique:users,email,'.$this->editingId],
            'nidn' => ['nullable', 'string', 'max:30', 'unique:asesor,nidn,'.$this->editingId],
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

    public function edit(int $asesorId): void
    {
        $asesor = Asesor::with('user')->findOrFail($asesorId);
        $this->editingId = $asesor->id;
        $this->name = $asesor->user->name;
        $this->username = $asesor->user->username;
        $this->email = $asesor->user->email;
        $this->nidn = $asesor->nidn ?? '';
        $this->bidang_keahlian = $asesor->bidang_keahlian ?? '';
        $this->no_hp = $asesor->no_hp ?? '';
        $this->showModal = true;
    }

    public function simpan(): void
    {
        $data = $this->validate();

        if ($this->editingId) {
            $asesor = Asesor::findOrFail($this->editingId);
            $asesor->user->update([
                'name' => $data['name'],
                'username' => $data['username'],
                'email' => $data['email'],
            ]);
            $asesor->update([
                'nidn' => $data['nidn'] ?: null,
                'bidang_keahlian' => $data['bidang_keahlian'] ?: null,
                'no_hp' => $data['no_hp'] ?: null,
            ]);
            session()->flash('success', 'Data asesor berhasil diperbarui.');
            $this->closeModal();

            return;
        }

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

    public function resetPassword(int $asesorId): void
    {
        $asesor = Asesor::with('user')->findOrFail($asesorId);
        $password = Str::password(12);
        $asesor->user->update(['password' => Hash::make($password)]);

        $this->generatedPassword = $password;
        $this->generatedFor = $asesor->user->username;
    }

    public function toggleAktif(int $asesorId): void
    {
        $asesor = Asesor::findOrFail($asesorId);
        $asesor->update(['is_aktif' => ! $asesor->is_aktif]);
        session()->flash('success', 'Status asesor diperbarui.');
    }

    public function toggleLock(int $asesorId): void
    {
        $asesor = Asesor::with('user')->findOrFail($asesorId);
        $asesor->user->update(['is_locked' => ! $asesor->user->is_locked]);
        session()->flash('success', $asesor->user->is_locked ? 'Akun dikunci.' : 'Akun dibuka kembali.');
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
        $this->reset(['editingId', 'name', 'username', 'email', 'nidn', 'bidang_keahlian', 'no_hp']);
        $this->resetErrorBag();
    }

    public function render()
    {
        $asesorList = Asesor::join('users', 'users.id', '=', 'asesor.user_id')
            ->select('asesor.*')
            ->with('user')
            ->withCount('penugasan')
            ->when($this->search, function ($q) {
                $q->whereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$this->search}%")->orWhere('username', 'like', "%{$this->search}%"))
                    ->orWhere('nidn', 'like', "%{$this->search}%");
            })
            ->orderBy('users.name')
            ->paginate($this->barisPerHalaman());

        return view('livewire.super-admin.asesor-manager', [
            'asesorList' => $asesorList,
        ]);
    }
}
