<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Guru;
use App\Http\Controllers\Industri;
use App\Http\Controllers\LogbookBuktiController;
use App\Http\Controllers\Siswa;
use App\Http\Controllers\Superadmin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function (Request $request) {
    return $request->user()
        ? redirect()->route($request->user()->role->homeRoute())
        : redirect()->route('login');
})->name('home');

// Login terpisah per role (keputusan 13 no. 32). `portal` menentukan role yang diterima.
Route::middleware('guest')->group(function () {
    foreach (['login' => 'siswa', 'login/guru' => 'guru', 'login/industri' => 'industri', 'login/admin' => 'superadmin'] as $uri => $portal) {
        $nama = $portal === 'siswa' ? 'login' : 'login.'.($portal === 'superadmin' ? 'admin' : $portal);

        Route::get($uri, [AuthenticatedSessionController::class, 'create'])->defaults('portal', $portal)->name($nama);
        Route::post($uri, [AuthenticatedSessionController::class, 'store'])->defaults('portal', $portal)->name("{$nama}.store");
    }
});

Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', 'role:superadmin'])->prefix('superadmin')->name('superadmin.')->group(function () {
    Route::get('/', Superadmin\DashboardController::class)->name('dashboard');

    Route::resource('akun', Superadmin\AkunController::class)->except(['show']);
    Route::patch('akun/{akun}/status', [Superadmin\AkunController::class, 'ubahStatus'])->name('akun.status');

    Route::resource('perusahaan', Superadmin\PerusahaanController::class)->except('show');
    Route::post('perusahaan/{perusahaan}/dokumen', [Superadmin\DokumenIndustriController::class, 'store'])->name('perusahaan.dokumen.store');
    Route::get('perusahaan/{perusahaan}/dokumen/{dokumen}', [Superadmin\DokumenIndustriController::class, 'show'])->name('perusahaan.dokumen.show');
    Route::delete('perusahaan/{perusahaan}/dokumen/{dokumen}', [Superadmin\DokumenIndustriController::class, 'destroy'])->name('perusahaan.dokumen.destroy');

    Route::resource('kelompok', Superadmin\KelompokMagangController::class)->except(['show']);

    Route::resource('program-keahlian', Superadmin\ProgramKeahlianController::class)
        ->only(['index', 'store', 'update', 'destroy'])
        ->parameters(['program-keahlian' => 'programKeahlian']);

    Route::get('materi', [Superadmin\PemantauanController::class, 'materi'])->name('materi.index');
    Route::get('materi/{materi}', [Superadmin\PemantauanController::class, 'materiShow'])->name('materi.show');
    Route::get('logbook', [Superadmin\PemantauanController::class, 'logbook'])->name('logbook.index');
    Route::get('assessment', [Superadmin\PemantauanController::class, 'assessment'])->name('assessment.index');
});

Route::middleware(['auth', 'role:guru'])->prefix('guru')->name('guru.')->group(function () {
    Route::get('/', Guru\DashboardController::class)->name('dashboard');

    Route::get('rekap', Guru\RekapController::class)->name('rekap');
    Route::get('siswa/{siswa}', [Guru\SiswaController::class, 'show'])->name('siswa.show');

    Route::resource('kompetensi', Guru\KompetensiController::class)->except(['show', 'destroy']);
    Route::resource('materi', Guru\MateriController::class)->except('destroy');

    Route::get('dokumen', [Guru\DokumenSekolahController::class, 'index'])->name('dokumen.index');
    Route::post('dokumen', [Guru\DokumenSekolahController::class, 'store'])->name('dokumen.store');
    Route::get('dokumen/{dokumen}', [Guru\DokumenSekolahController::class, 'show'])->name('dokumen.show');
    Route::delete('dokumen/{dokumen}', [Guru\DokumenSekolahController::class, 'destroy'])->name('dokumen.destroy');
});

Route::middleware(['auth', 'role:industri'])->prefix('industri')->name('industri.')->group(function () {
    Route::get('/', Industri\DashboardController::class)->name('dashboard');

    Route::get('siswa/{siswa}', [Industri\SiswaController::class, 'show'])->name('siswa.show');
    Route::post('siswa/{siswa}/kompetensi/{kompetensi}/verifikasi', [Industri\SiswaController::class, 'verifikasi'])
        ->name('siswa.verifikasi');

    Route::get('materi', [Industri\MateriController::class, 'index'])->name('materi.index');
    Route::get('materi/{materi}', [Industri\MateriController::class, 'show'])->name('materi.show');
    Route::post('materi/{materi}/verifikasi', [Industri\MateriController::class, 'periksa'])->name('materi.verifikasi');
});

Route::middleware(['auth', 'role:siswa'])->prefix('siswa')->name('siswa.')->group(function () {
    Route::get('mulai', [Siswa\MulaiPendampinganController::class, 'show'])->name('mulai');
    Route::post('mulai', [Siswa\MulaiPendampinganController::class, 'store'])->name('mulai.store');

    Route::middleware('pendampingan')->group(function () {
        Route::get('/', Siswa\DashboardController::class)->name('dashboard');
        Route::get('peta-kompetensi', [Siswa\KompetensiController::class, 'peta'])->name('peta');
        Route::get('learning-gap', [Siswa\KompetensiController::class, 'gap'])->name('gap');

        Route::get('belajar', [Siswa\BelajarController::class, 'index'])->name('belajar.index');
        Route::get('belajar/{materi}', [Siswa\BelajarController::class, 'show'])->name('belajar.show');
        Route::post('belajar/{materi}/kuis', [Siswa\BelajarController::class, 'kuis'])->name('belajar.kuis');

        Route::get('logbook', [Siswa\LogbookController::class, 'index'])->name('logbook.index');
        Route::post('logbook', [Siswa\LogbookController::class, 'store'])->name('logbook.store');
        Route::put('logbook/{logbook}', [Siswa\LogbookController::class, 'update'])->name('logbook.update');
        Route::post('logbook/{logbook}/analisis', [Siswa\LogbookController::class, 'analisis'])
            ->middleware('throttle:ai-mentor')
            ->name('logbook.analisis');

        // AI Mentor (JSON untuk floating chatbot).
        Route::get('mentor', [Siswa\AiMentorController::class, 'riwayat'])->name('mentor.riwayat');
        Route::post('mentor', [Siswa\AiMentorController::class, 'kirim'])->middleware('throttle:ai-mentor')->name('mentor.kirim');
        Route::delete('mentor', [Siswa\AiMentorController::class, 'hapus'])->name('mentor.hapus');

        Route::put('kompetensi-utama', [Siswa\PengaturanPendampinganController::class, 'kompetensiUtama'])->name('kompetensi-utama');
        Route::put('unit-kerja', [Siswa\PengaturanPendampinganController::class, 'unitKerja'])->name('unit-kerja');

        Route::get('assessment', Siswa\AssessmentController::class)->name('assessment');
        Route::get('progress', Siswa\ProgressController::class)->name('progress');
    });
});

// Bukti kegiatan logbook: siswa pemilik, guru kelompoknya, industri perusahaannya, superadmin.
Route::get('logbook/{logbook}/bukti', LogbookBuktiController::class)
    ->middleware('auth')
    ->name('logbook.bukti');
