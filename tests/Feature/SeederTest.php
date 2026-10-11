<?php

use App\Enums\Role;
use App\Models\KelompokMagang;
use App\Models\Kompetensi;
use App\Models\Materi;
use App\Models\Perusahaan;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoSeeder;
use Illuminate\Support\Facades\Hash;

test('seeder production hanya membuat satu akun superadmin dari .env', function () {
    config(['magang.admin' => ['nama' => 'Admin Sekolah', 'email' => 'admin@contoh.test', 'password' => 'rahasia-uji-123']]);

    $this->seed(DatabaseSeeder::class);
    $this->seed(DatabaseSeeder::class); // aman dijalankan ulang

    $admin = User::sole();
    expect($admin->email)->toBe('admin@contoh.test')
        ->and($admin->role)->toBe(Role::Superadmin)
        ->and($admin->status_aktif)->toBeTrue()
        ->and(Hash::check('rahasia-uji-123', $admin->password))->toBeTrue()
        ->and(Perusahaan::count())->toBe(0)
        ->and(Materi::count())->toBe(0);

    $this->post(route('login.admin.store'), ['email' => 'admin@contoh.test', 'password' => 'rahasia-uji-123'])
        ->assertRedirect(route('superadmin.dashboard'));
});

test('seeder production menolak jalan tanpa ADMIN_PASSWORD', function () {
    config(['magang.admin.password' => null]);

    $this->seed(DatabaseSeeder::class);
})->throws(RuntimeException::class, 'ADMIN_PASSWORD');

test('DemoSeeder membuat data contoh untuk pengembangan lokal', function () {
    $this->seed(DemoSeeder::class);

    expect(User::where('role', Role::Superadmin)->count())->toBe(1)
        ->and(User::where('role', Role::Guru)->count())->toBe(2)
        ->and(User::where('role', Role::Industri)->count())->toBe(2)
        ->and(User::where('role', Role::Siswa)->count())->toBe(6)
        ->and(Perusahaan::count())->toBe(2)
        ->and(KelompokMagang::count())->toBe(2)
        ->and(Kompetensi::count())->toBeGreaterThan(0)
        ->and(Materi::count())->toBe(2);

    Materi::with('langkah.media', 'kuis.soal')->get()->each(function (Materi $materi) {
        expect($materi->langkah)->toHaveCount(8)
            ->and($materi->kuis->soal)->not->toBeEmpty()
            ->and($materi->langkah->flatMap->media->pluck('jenis.value')->unique()->sort()->values()->all())
            ->toBe(['dokumen', 'gambar', 'youtube']);
    });
});
