<?php

use App\Http\Controllers\Auth\LogoutController;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\UbahPassword;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return Auth::check()
        ? redirect()->route(Auth::user()->dashboardRoute())
        : redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
});

Route::post('/logout', LogoutController::class)->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/ubah-password', UbahPassword::class)->name('ubah-password');
});

require __DIR__.'/super-admin.php';
require __DIR__.'/admin-prodi.php';
require __DIR__.'/asesor.php';
require __DIR__.'/mahasiswa.php';
