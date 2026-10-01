<?php

use App\Enums\Role;
use App\Models\KelompokMagang;
use App\Models\Kompetensi;
use App\Models\Materi;
use App\Models\Perusahaan;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;

test('seeder membuat data contoh sesuai rencana Tahap 1', function () {
    $this->seed(DatabaseSeeder::class);

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
