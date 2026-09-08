<?php

use App\Http\Controllers\AdminProdi\RekapitulasiExcelController;
use App\Http\Controllers\AdminProdi\RekapitulasiPdfController;
use App\Livewire\AdminProdi\AsesorDirectory;
use App\Livewire\AdminProdi\Dashboard;
use App\Livewire\AdminProdi\MahasiswaManager;
use App\Livewire\AdminProdi\MatakuliahManager;
use App\Livewire\AdminProdi\MonitoringPenilaian;
use App\Livewire\AdminProdi\Rekapitulasi;
use App\Livewire\AdminProdi\SkemaManager;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:admin_prodi'])
    ->prefix('admin-prodi')
    ->name('admin-prodi.')
    ->group(function () {
        Route::get('/', Dashboard::class)->name('dashboard');
        Route::get('/matakuliah', MatakuliahManager::class)->name('matakuliah');
        Route::get('/skema', SkemaManager::class)->name('skema');
        Route::get('/asesor', AsesorDirectory::class)->name('asesor');
        Route::get('/mahasiswa', MahasiswaManager::class)->name('mahasiswa');
        Route::get('/monitoring', MonitoringPenilaian::class)->name('monitoring');
        Route::get('/monitoring/{mahasiswa}', Rekapitulasi::class)->name('rekapitulasi');
        Route::get('/monitoring/{mahasiswa}/pdf', RekapitulasiPdfController::class)->name('rekapitulasi.pdf');
        Route::get('/monitoring/{mahasiswa}/excel', RekapitulasiExcelController::class)->name('rekapitulasi.excel');
    });
