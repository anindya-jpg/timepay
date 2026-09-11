<?php

use App\Http\Controllers\Admin\AbsensiController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\ApprovalLemburController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\HariLiburController;
use App\Http\Controllers\Admin\JabatanController;
use App\Http\Controllers\Admin\PengaturanJamKerjaController;
use App\Http\Controllers\Admin\PenggajianController;
use App\Http\Controllers\Admin\PotonganRuleController;
use App\Http\Controllers\Admin\TarifLemburController;
use App\Http\Controllers\Admin\TunjanganController;
use App\Http\Middleware\EnsureAdminAuthenticated;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Routes (TimePay)
|--------------------------------------------------------------------------
|
| Terpisah total dari routes/web.php bawaan starter kit (yang masih
| terikat sistem teams/users). Semua di-prefix 'admin' dan pakai
| guard 'admin' sendiri.
|
*/

Route::prefix('admin')->name('admin.')->group(function () {

    // Guest only (belum login)
Route::middleware(\App\Http\Middleware\RedirectIfAdminAuthenticated::class)->group(function () {
        Route::get('login', [AuthController::class, 'create'])->name('login');
        Route::post('login', [AuthController::class, 'store'])->name('login.store');
    });

    // Butuh login sebagai admin
    Route::middleware(EnsureAdminAuthenticated::class)->group(function () {
        Route::post('logout', [AuthController::class, 'destroy'])->name('logout');

        Route::get('dashboard', DashboardController::class)->name('dashboard');

        Route::resource('karyawan', \App\Http\Controllers\Admin\KaryawanController::class)
    ->parameters(['karyawan' => 'karyawan'])
    ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('jabatan', JabatanController::class)->only(['index', 'store', 'update', 'destroy']);

        // Data Tunjangan: 2 tab (master + nominal per jabatan)
        Route::get('tunjangan', [TunjanganController::class, 'index'])->name('tunjangan.index');
        Route::post('tunjangan', [TunjanganController::class, 'store'])->name('tunjangan.store');
        Route::put('tunjangan/{tunjangan}', [TunjanganController::class, 'update'])->name('tunjangan.update');
        Route::delete('tunjangan/{tunjangan}', [TunjanganController::class, 'destroy'])->name('tunjangan.destroy');

        Route::get('tunjangan-nominal', [TunjanganController::class, 'nominalIndex'])->name('tunjangan.nominal.index');
        Route::post('tunjangan-nominal', [TunjanganController::class, 'nominalStore'])->name('tunjangan.nominal.store');
        Route::put('tunjangan-nominal/{jabatanTunjangan}', [TunjanganController::class, 'nominalUpdate'])->name('tunjangan.nominal.update');
        Route::delete('tunjangan-nominal/{jabatanTunjangan}', [TunjanganController::class, 'nominalDestroy'])->name('tunjangan.nominal.destroy');

Route::get('pengaturan-lokasi', [\App\Http\Controllers\Admin\PengaturanLokasiController::class, 'edit'])->name('pengaturan-lokasi.edit');
Route::put('pengaturan-lokasi', [\App\Http\Controllers\Admin\PengaturanLokasiController::class, 'update'])->name('pengaturan-lokasi.update');

        Route::get('pengaturan-jam-kerja', [PengaturanJamKerjaController::class, 'edit'])->name('jam-kerja.edit');
        Route::put('pengaturan-jam-kerja', [PengaturanJamKerjaController::class, 'update'])->name('jam-kerja.update');

        Route::resource('hari-libur', HariLiburController::class)->only(['index', 'store', 'update', 'destroy']);

        Route::get('tarif-lembur', [TarifLemburController::class, 'edit'])->name('tarif-lembur.edit');
        Route::put('tarif-lembur', [TarifLemburController::class, 'update'])->name('tarif-lembur.update');

        Route::resource('potongan-rule', PotonganRuleController::class)->only(['index', 'store', 'update', 'destroy']);

        Route::resource('absensi', AbsensiController::class)->only(['index', 'store', 'update', 'destroy']);
        
        Route::put('approval-lembur/{absensi}', [ApprovalLemburController::class, 'update'])->name('approval-lembur.update');
        Route::get('approval-lembur', [ApprovalLemburController::class, 'index'])->name('approval-lembur.index');
        Route::post('approval-lembur/{absensi}/approve', [ApprovalLemburController::class, 'approve'])->name('approval-lembur.approve');
        Route::post('approval-lembur/{absensi}/reject', [ApprovalLemburController::class, 'reject'])->name('approval-lembur.reject');

        Route::get('penggajian', [PenggajianController::class, 'index'])->name('penggajian.index');
Route::post('penggajian/generate', [PenggajianController::class, 'generate'])->name('penggajian.generate');
Route::post('penggajian/finalisasi', [PenggajianController::class, 'finalisasi'])->name('penggajian.finalisasi');
Route::get('penggajian/rekap/pdf', [PenggajianController::class, 'rekapPdf'])->name('penggajian.rekap-pdf');
Route::get('penggajian/{penggajian}/pdf', [PenggajianController::class, 'slipPdf'])->name('penggajian.pdf');
Route::get('penggajian/{penggajian}', [PenggajianController::class, 'show'])->name('penggajian.show');

        Route::resource('admin-akun', AdminController::class)->only(['index', 'store', 'update', 'destroy']);
    });
});
