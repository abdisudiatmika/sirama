<?php

use App\Http\Controllers\AdminProdi\AsesorController;
use App\Http\Controllers\AdminProdi\DokumenZipController;
use App\Http\Controllers\AdminProdi\EksporSiakadController;
use App\Http\Controllers\AdminProdi\FormulirWordController;
use App\Http\Controllers\AdminProdi\LampiranLamaController;
use App\Http\Controllers\AdminProdi\RekapitulasiExcelController;
use App\Http\Controllers\AdminProdi\RekapitulasiPdfController;
use App\Http\Controllers\AdminProdi\TemplateNilaiController;
use App\Http\Controllers\AdminProdi\TemplateNilaiMassalController;
use App\Livewire\AdminProdi\ArsipSistemLama;
use App\Livewire\AdminProdi\AsesorDirectory;
use App\Livewire\AdminProdi\Dashboard;
use App\Livewire\AdminProdi\InputNilai;
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
        Route::get('/asesor/ekspor', [AsesorController::class, 'ekspor'])->name('asesor.ekspor');
        Route::get('/asesor/{asesor}/tanda-tangan', [AsesorController::class, 'tandaTangan'])->name('asesor.ttd');
        Route::get('/mahasiswa', MahasiswaManager::class)->name('mahasiswa');
        Route::get('/mahasiswa/{mahasiswa}/form/{nomor}', FormulirWordController::class)->whereNumber('nomor')->name('mahasiswa.formulir');
        Route::get('/mahasiswa/{mahasiswa}/unduh-semua', DokumenZipController::class)->name('mahasiswa.dokumen-zip');
        Route::get('/arsip-lama', ArsipSistemLama::class)->name('arsip-lama');
        Route::get('/arsip-lama/file/{lampiran}', [LampiranLamaController::class, 'file'])->name('arsip-lama.file');
        Route::get('/arsip-lama/{mahasiswa}/unduh', [LampiranLamaController::class, 'zip'])->name('arsip-lama.zip');
        Route::get('/ekspor-siakad', EksporSiakadController::class)->name('ekspor-siakad');
        Route::get('/mahasiswa/{mahasiswa}/siakad', [EksporSiakadController::class, 'mahasiswa'])->name('mahasiswa.siakad');
        Route::get('/mahasiswa/{mahasiswa}/template-nilai', TemplateNilaiController::class)->name('mahasiswa.template-nilai');
        Route::get('/input-nilai', InputNilai::class)->name('input-nilai');
        Route::get('/input-nilai/template', TemplateNilaiMassalController::class)->name('input-nilai.template');
        Route::get('/monitoring', MonitoringPenilaian::class)->name('monitoring');
        Route::get('/monitoring/{mahasiswa}', Rekapitulasi::class)->name('rekapitulasi');
        Route::get('/monitoring/{mahasiswa}/pdf', RekapitulasiPdfController::class)->name('rekapitulasi.pdf');
        Route::get('/monitoring/{mahasiswa}/excel', RekapitulasiExcelController::class)->name('rekapitulasi.excel');
    });
