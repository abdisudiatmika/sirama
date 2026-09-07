<?php

use App\Livewire\SuperAdmin\AdminProdiManager;
use App\Livewire\SuperAdmin\AsesorManager;
use App\Livewire\SuperAdmin\Dashboard;
use App\Livewire\SuperAdmin\ProdiManager;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:super_admin'])
    ->prefix('super-admin')
    ->name('super-admin.')
    ->group(function () {
        Route::get('/', Dashboard::class)->name('dashboard');
        Route::get('/prodi', ProdiManager::class)->name('prodi');
        Route::get('/admin-prodi', AdminProdiManager::class)->name('admin-prodi');
        Route::get('/asesor', AsesorManager::class)->name('asesor');
    });
