<?php

use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Testing\AssertableInertia as Assert;

test('halaman login dapat dibuka', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('auth/login'));
});

test('tamu diarahkan ke login dari halaman utama dan halaman role', function (string $url) {
    $this->get($url)->assertRedirect(route('login'));
})->with(['/', '/superadmin', '/guru', '/industri', '/siswa']);

test('setiap role diarahkan ke halamannya setelah login', function (string $state, string $tujuan) {
    $user = User::factory()->{$state}()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route($tujuan));

    $this->assertAuthenticatedAs($user);
})->with([
    'superadmin' => ['superadmin', 'superadmin.dashboard'],
    'guru' => ['guru', 'guru.dashboard'],
    'industri' => ['industri', 'industri.dashboard'],
    'siswa' => ['siswa', 'siswa.dashboard'],
]);

test('kata sandi salah ditolak dengan pesan bahasa Indonesia', function () {
    $user = User::factory()->guru()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'salah',
    ])->assertSessionHasErrors(['email' => 'Email atau kata sandi salah.']);

    $this->assertGuest();
});

test('akun nonaktif tidak bisa login', function () {
    $user = User::factory()->siswa()->nonaktif()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('login dibatasi setelah 5 percobaan gagal', function () {
    $user = User::factory()->guru()->create();

    foreach (range(1, 5) as $_) {
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'salah']);
    }

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();

    RateLimiter::clear(strtolower($user->email).'|127.0.0.1');
});

test('pengguna yang sudah login dan membuka /login diarahkan ke halaman role-nya', function () {
    $user = User::factory()->industri()->create();

    $this->actingAs($user)->get(route('login'))->assertRedirect(route('industri.dashboard'));
    $this->actingAs($user)->get('/')->assertRedirect(route('industri.dashboard'));
});

test('pengguna dapat keluar', function () {
    $user = User::factory()->siswa()->create();

    $this->actingAs($user)->post(route('logout'))->assertRedirect(route('login'));

    $this->assertGuest();
});

test('tidak ada route registrasi atau reset password', function (string $url) {
    $this->get($url)->assertNotFound();
})->with(['/register', '/forgot-password', '/reset-password/token']);
