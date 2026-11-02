<?php

namespace App\Livewire\SuperAdmin;

use App\Livewire\Concerns\WithBarisPerHalaman;
use App\Models\Prodi;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Admin Prodi')]
class AdminProdiManager extends Component
{
    use WithBarisPerHalaman, WithPagination;

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $username = '';

    public string $email = '';

    /** @var array<int> */
    public array $selectedProdi = [];

    public string $search = '';

    public ?string $generatedPassword = null;

    public ?string $generatedFor = null;

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'username' => ['required', 'string', 'max:50', 'unique:users,username,'.$this->editingId],
            'email' => ['required', 'email', 'max:150', 'unique:users,email,'.$this->editingId],
            'selectedProdi' => ['required', 'array', 'min:1'],
            'selectedProdi.*' => ['exists:prodi,id'],
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
        $user = User::with('prodiDikelola')->findOrFail($id);
        $this->editingId = $user->id;
        $this->name = $user->name;
        $this->username = $user->username;
        $this->email = $user->email;
        $this->selectedProdi = $user->prodiDikelola->pluck('id')->toArray();
        $this->showModal = true;
    }

    public function simpan(): void
    {
        $data = $this->validate();

        if ($this->editingId) {
            $user = User::findOrFail($this->editingId);
            $user->update([
                'name' => $data['name'],
                'username' => $data['username'],
                'email' => $data['email'],
            ]);
            session()->flash('success', 'Akun admin prodi berhasil diperbarui.');
        } else {
            $password = Str::password(12);
            $user = User::create([
                'name' => $data['name'],
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => Hash::make($password),
            ]);
            $user->assignRole('admin_prodi');
            $this->generatedPassword = $password;
            $this->generatedFor = $user->username;
        }

        $user->prodiDikelola()->sync($data['selectedProdi']);

        if (! $this->editingId) {
            $this->showModal = false;
            $this->reset(['editingId', 'name', 'username', 'email', 'selectedProdi']);
            $this->resetErrorBag();

            return;
        }

        session()->flash('success', 'Akun admin prodi berhasil diperbarui.');
        $this->closeModal();
    }

    public function resetPassword(int $id): void
    {
        $user = User::findOrFail($id);
        $password = Str::password(12);
        $user->update(['password' => Hash::make($password)]);

        $this->generatedPassword = $password;
        $this->generatedFor = $user->username;
    }

    public function toggleLock(int $id): void
    {
        $user = User::findOrFail($id);
        $user->update(['is_locked' => ! $user->is_locked]);
        session()->flash('success', $user->is_locked ? 'Akun dikunci.' : 'Akun dibuka kembali.');
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
        $this->reset(['editingId', 'name', 'username', 'email', 'selectedProdi']);
        $this->resetErrorBag();
    }

    public function render()
    {
        $admins = User::role('admin_prodi')
            ->with('prodiDikelola')
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%")->orWhere('username', 'like', "%{$this->search}%"))
            ->orderBy('name')
            ->paginate($this->barisPerHalaman());

        return view('livewire.super-admin.admin-prodi-manager', [
            'admins' => $admins,
            'prodiOptions' => Prodi::orderBy('nama')->get(),
        ]);
    }
}
