<?php

use App\Livewire\Mahasiswa\Dashboard;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:mahasiswa'])
    ->prefix('mahasiswa')
    ->name('mahasiswa.')
    ->group(function () {
        Route::get('/', Dashboard::class)->name('dashboard');
    });
