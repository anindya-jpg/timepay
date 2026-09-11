<?php

use App\Http\Controllers\Karyawan\AbsensiKaryawanController;
use App\Http\Controllers\Karyawan\AuthController;
use App\Http\Controllers\Karyawan\DashboardController;
use App\Http\Controllers\Karyawan\ProfilController;
use App\Http\Controllers\Karyawan\RiwayatAbsensiController;
use App\Http\Controllers\Karyawan\SlipGajiController;
use App\Http\Middleware\EnsureKaryawanAuthenticated;
use App\Http\Middleware\RedirectIfKaryawanAuthenticated;
use Illuminate\Support\Facades\Route;

Route::prefix('karyawan')->name('karyawan.')->group(function () {

    Route::middleware(RedirectIfKaryawanAuthenticated::class)->group(function () {
        Route::get('login', [AuthController::class, 'create'])->name('login');
        Route::post('login', [AuthController::class, 'store'])->name('login.store');
    });

    Route::middleware(EnsureKaryawanAuthenticated::class)->group(function () {
        Route::post('logout', [AuthController::class, 'destroy'])->name('logout');

        Route::get('dashboard', DashboardController::class)->name('dashboard');

        Route::get('absen-masuk', [AbsensiKaryawanController::class, 'createMasuk'])->name('absen-masuk');
        Route::post('absen-masuk', [AbsensiKaryawanController::class, 'storeMasuk'])->name('absen-masuk.store');

        Route::get('absen-pulang', [AbsensiKaryawanController::class, 'createPulang'])->name('absen-pulang');
        Route::post('absen-pulang', [AbsensiKaryawanController::class, 'storePulang'])->name('absen-pulang.store');
       
        Route::get('izin-sakit', [AbsensiKaryawanController::class, 'createIzinSakit'])->name('izin-sakit');
        Route::post('izin-sakit', [AbsensiKaryawanController::class, 'storeIzinSakit'])->name('izin-sakit.store');

        Route::get('riwayat-absensi', [RiwayatAbsensiController::class, 'index'])->name('riwayat-absensi.index');

        Route::get('slip-gaji', [SlipGajiController::class, 'index'])->name('slip-gaji.index');
        Route::get('slip-gaji/pdf', [SlipGajiController::class, 'pdf'])->name('slip-gaji.pdf');

        Route::get('profil', [ProfilController::class, 'edit'])->name('profil.edit');
        Route::put('profil', [ProfilController::class, 'update'])->name('profil.update');
    });
});