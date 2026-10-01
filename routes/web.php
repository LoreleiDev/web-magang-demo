<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Superadmin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function (Request $request) {
    return $request->user()
        ? redirect()->route($request->user()->role->homeRoute())
        : redirect()->route('login');
})->name('home');

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', 'role:superadmin'])->prefix('superadmin')->name('superadmin.')->group(function () {
    Route::get('/', Superadmin\DashboardController::class)->name('dashboard');

    Route::resource('akun', Superadmin\AkunController::class)->except(['show', 'destroy']);
    Route::patch('akun/{akun}/status', [Superadmin\AkunController::class, 'ubahStatus'])->name('akun.status');

    Route::resource('perusahaan', Superadmin\PerusahaanController::class)->except('show');
    Route::post('perusahaan/{perusahaan}/dokumen', [Superadmin\DokumenIndustriController::class, 'store'])->name('perusahaan.dokumen.store');
    Route::get('perusahaan/{perusahaan}/dokumen/{dokumen}', [Superadmin\DokumenIndustriController::class, 'show'])->name('perusahaan.dokumen.show');
    Route::delete('perusahaan/{perusahaan}/dokumen/{dokumen}', [Superadmin\DokumenIndustriController::class, 'destroy'])->name('perusahaan.dokumen.destroy');

    Route::resource('kelompok', Superadmin\KelompokMagangController::class)->except(['show', 'destroy']);

    Route::get('materi', [Superadmin\PemantauanController::class, 'materi'])->name('materi.index');
    Route::get('materi/{materi}', [Superadmin\PemantauanController::class, 'materiShow'])->name('materi.show');
    Route::get('logbook', [Superadmin\PemantauanController::class, 'logbook'])->name('logbook.index');
    Route::get('assessment', [Superadmin\PemantauanController::class, 'assessment'])->name('assessment.index');
});

Route::middleware(['auth', 'role:guru'])->prefix('guru')->name('guru.')->group(function () {
    Route::inertia('/', 'guru/dashboard')->name('dashboard');
});

Route::middleware(['auth', 'role:industri'])->prefix('industri')->name('industri.')->group(function () {
    Route::inertia('/', 'industri/dashboard')->name('dashboard');
});

Route::middleware(['auth', 'role:siswa'])->prefix('siswa')->name('siswa.')->group(function () {
    Route::inertia('/', 'siswa/dashboard')->name('dashboard');
});
