<?php

use App\Livewire\Asesor\Dashboard;
use App\Livewire\Asesor\DetailMahasiswa;
use App\Livewire\Asesor\FormPenilaian;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:asesor'])
    ->prefix('asesor')
    ->name('asesor.')
    ->group(function () {
        Route::get('/', Dashboard::class)->name('dashboard');
        Route::get('/mahasiswa/{mahasiswa}', DetailMahasiswa::class)->name('mahasiswa');
        Route::get('/mahasiswa/{mahasiswa}/mk/{pengajuanMk}', FormPenilaian::class)->name('penilaian');
    });
