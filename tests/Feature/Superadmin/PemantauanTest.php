<?php

use App\Models\Materi;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    $this->admin = User::where('email', 'admin@magangbridge.test')->firstOrFail();
});

test('dashboard superadmin menampilkan ringkasan', function () {
    $this->actingAs($this->admin)
        ->get(route('superadmin.dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('superadmin/dashboard')
            ->where('statistik.guru', 2)
            ->where('statistik.siswa', 6)
            ->where('statistik.perusahaan', 2)
            ->where('statistik.materi', 2)
            ->where('jumlahSiswaTanpaKelompok', 0));
});

test('superadmin melihat semua materi dan detailnya lengkap dengan kunci jawaban', function () {
    $this->actingAs($this->admin)
        ->get(route('superadmin.materi.index'))
        ->assertInertia(fn (Assert $page) => $page->has('materi.data', 2));

    $this->actingAs($this->admin)
        ->get(route('superadmin.materi.index', ['program' => 'RPL']))
        ->assertInertia(fn (Assert $page) => $page->has('materi.data', 1)->where('materi.data.0.program_keahlian', 'RPL'));

    $materi = Materi::firstOrFail();

    $this->actingAs($this->admin)
        ->get(route('superadmin.materi.show', $materi))
        ->assertInertia(fn (Assert $page) => $page
            ->component('superadmin/pemantauan/materi-detail')
            ->has('materi.langkah', 8)
            ->has('materi.soal.0.jawaban_benar')
            ->has('materi.langkah.2.media.0.url_embed'));
});

test('superadmin melihat logbook dan assessment semua siswa dengan filter', function () {
    $this->actingAs($this->admin)
        ->get(route('superadmin.logbook.index'))
        ->assertInertia(fn (Assert $page) => $page->has('logbook.data', 2));

    $this->actingAs($this->admin)
        ->get(route('superadmin.logbook.index', ['cari' => 'dewi']))
        ->assertInertia(fn (Assert $page) => $page->has('logbook.data', 1)->where('logbook.data.0.siswa', 'Dewi Anggraini'));

    $this->actingAs($this->admin)
        ->get(route('superadmin.assessment.index'))
        ->assertInertia(fn (Assert $page) => $page->has('hasil.data', 3));
});

test('halaman pemantauan tertutup untuk role lain', function (string $email) {
    $user = User::where('email', $email)->firstOrFail();

    foreach (['superadmin.materi.index', 'superadmin.logbook.index', 'superadmin.assessment.index'] as $route) {
        $this->actingAs($user)->get(route($route))->assertForbidden();
    }
})->with(['budi.santoso@magangbridge.test', 'hendra@nusantaranet.test', 'andi@siswa.test']);
