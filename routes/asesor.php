<?php

use App\Livewire\Asesor\Dashboard;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:asesor'])
    ->prefix('asesor')
    ->name('asesor.')
    ->group(function () {
        Route::get('/', Dashboard::class)->name('dashboard');
    });
