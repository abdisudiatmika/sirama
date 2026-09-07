<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Ubah Password')]
class UbahPassword extends Component
{
    public string $password_lama = '';

    public string $password_baru = '';

    public string $password_baru_confirmation = '';

    public function submit(): void
    {
        $this->validate([
            'password_lama' => ['required', 'current_password'],
            'password_baru' => ['required', 'string', 'min:8', 'confirmed', 'different:password_lama'],
        ], [
            'password_lama.current_password' => 'Password lama yang Anda masukkan salah.',
            'password_baru.different' => 'Password baru harus berbeda dari password lama.',
        ]);

        Auth::user()->forceFill([
            'password' => Hash::make($this->password_baru),
        ])->save();

        $this->reset(['password_lama', 'password_baru', 'password_baru_confirmation']);

        session()->flash('success', 'Password berhasil diubah.');
    }

    public function render()
    {
        return view('livewire.auth.ubah-password');
    }
}
