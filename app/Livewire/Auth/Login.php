<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.guest')]
class Login extends Component
{
    public string $login = '';

    public string $password = '';

    public function submit(): void
    {
        $this->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [], [
            'login' => 'username / email',
        ]);

        $throttleKey = Str::lower($this->login).'|'.request()->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, config('rpl.maks_percobaan_login'))) {
            $detik = RateLimiter::availableIn($throttleKey);
            $menit = (int) ceil($detik / 60);

            $this->addError('login', "Terlalu banyak percobaan login gagal. Silakan coba lagi dalam {$menit} menit.");

            return;
        }

        $user = User::query()
            ->where('username', $this->login)
            ->orWhere('email', $this->login)
            ->first();

        if (! $user || ! Auth::attempt(['email' => $user->email, 'password' => $this->password])) {
            RateLimiter::hit($throttleKey, config('rpl.jeda_setelah_gagal_login_menit') * 60);

            $this->addError('login', 'Username/email atau password salah.');

            return;
        }

        if ($user->is_locked) {
            Auth::logout();
            $this->addError('login', 'Akun Anda terkunci. Silakan hubungi admin prodi.');

            return;
        }

        RateLimiter::clear($throttleKey);
        request()->session()->regenerate();

        $user->forceFill([
            'last_login_at' => now(),
            'failed_login_attempts' => 0,
        ])->save();

        $this->redirect(route($user->dashboardRoute()), navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
