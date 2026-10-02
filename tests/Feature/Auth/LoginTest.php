<?php

use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Testing\AssertableInertia as Assert;

test('halaman login utama dan login siswa dapat dibuka', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('auth/login'));

    $this->get(route('siswa.login'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('auth/login-siswa'));
});

test('tamu diarahkan ke login yang sesuai', function (string $url, string $tujuan) {
    $this->get($url)->assertRedirect(route($tujuan));
})->with([
    ['/', 'login'],
    ['/superadmin', 'login'],
    ['/guru', 'login'],
    ['/industri', 'login'],
    ['/siswa', 'siswa.login'],
    ['/siswa/logbook', 'siswa.login'],
]);

test('superadmin, guru, industri diarahkan ke halamannya setelah login', function (string $state, string $tujuan) {
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
]);

test('siswa login di halaman khusus lalu diarahkan ke Mulai Pendampingan', function () {
    $siswa = User::factory()->siswa()->create();

    $this->post(route('siswa.login.store'), [
        'email' => $siswa->email,
        'password' => 'password',
    ])->assertRedirect(route('siswa.mulai'));

    $this->assertAuthenticatedAs($siswa);
});

test('siswa tidak bisa login di halaman utama, dan role lain tidak bisa login di halaman siswa', function () {
    $siswa = User::factory()->siswa()->create();
    $guru = User::factory()->guru()->create();

    $this->post(route('login.store'), ['email' => $siswa->email, 'password' => 'password'])
        ->assertSessionHasErrors(['email' => 'Akun siswa masuk lewat halaman Login Siswa.']);
    $this->assertGuest();

    $this->post(route('siswa.login.store'), ['email' => $guru->email, 'password' => 'password'])
        ->assertSessionHasErrors('email');
    $this->assertGuest();
});

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

    $this->post(route('siswa.login.store'), [
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

test('keluar mengarah ke halaman login yang sesuai', function () {
    $siswa = User::factory()->siswa()->create();
    $this->actingAs($siswa)->post(route('logout'))->assertRedirect(route('siswa.login'));
    $this->assertGuest();

    $guru = User::factory()->guru()->create();
    $this->actingAs($guru)->post(route('logout'))->assertRedirect(route('login'));
    $this->assertGuest();
});

test('tidak ada route registrasi atau reset password', function (string $url) {
    $this->get($url)->assertNotFound();
})->with(['/register', '/forgot-password', '/reset-password/token', '/siswa/register']);
