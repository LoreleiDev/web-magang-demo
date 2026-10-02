<?php

use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Testing\AssertableInertia as Assert;

/** URL login, nama route, dan state factory per role (keputusan 13 no. 32). */
dataset('portal', [
    'siswa' => ['/login', 'login', 'siswa'],
    'guru' => ['/login/guru', 'login.guru', 'guru'],
    'industri' => ['/login/industri', 'login.industri', 'industri'],
    'admin' => ['/login/admin', 'login.admin', 'superadmin'],
]);

test('keempat halaman login dapat dibuka dan tidak memuat tautan ke login lain', function (string $url, string $route, string $role) {
    $this->get($url)
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('auth/login')
            ->where('portal', $role)
            ->where('aksi', route("{$route}.store"))
            ->missing('tautanLain'));
})->with('portal');

test('tamu diarahkan ke halaman login sesuai area yang dibuka', function (string $url, string $tujuan) {
    $this->get($url)->assertRedirect(route($tujuan));
})->with([
    ['/', 'login'],
    ['/siswa', 'login'],
    ['/siswa/logbook', 'login'],
    ['/guru', 'login.guru'],
    ['/guru/materi', 'login.guru'],
    ['/industri', 'login.industri'],
    ['/superadmin', 'login.admin'],
    ['/superadmin/akun', 'login.admin'],
]);

test('setiap role berhasil login di halamannya sendiri', function (string $url, string $route, string $role) {
    $user = User::factory()->{$role}()->create();

    $this->post(route("{$route}.store"), ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route($user->role->homeRoute()));

    $this->assertAuthenticatedAs($user);
})->with('portal');

test('akun ditolak di halaman login role lain', function (string $url, string $route, string $role) {
    foreach (['siswa', 'guru', 'industri', 'superadmin'] as $lain) {
        if ($lain === $role) {
            continue;
        }

        $user = User::factory()->{$lain}()->create();

        $this->post(route("{$route}.store"), ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }
})->with('portal');

test('kata sandi salah ditolak dengan pesan bahasa Indonesia', function () {
    $user = User::factory()->guru()->create();

    $this->post(route('login.guru.store'), [
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
        $this->post(route('login.guru.store'), ['email' => $user->email, 'password' => 'salah']);
    }

    $this->post(route('login.guru.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();

    RateLimiter::clear(strtolower($user->email).'|127.0.0.1');
});

test('pengguna yang sudah login dan membuka halaman login diarahkan ke halaman role-nya', function () {
    $user = User::factory()->industri()->create();

    $this->actingAs($user)->get(route('login.industri'))->assertRedirect(route('industri.dashboard'));
    $this->actingAs($user)->get(route('login'))->assertRedirect(route('industri.dashboard'));
    $this->actingAs($user)->get('/')->assertRedirect(route('industri.dashboard'));
});

test('keluar mengarah ke halaman login role tersebut', function (string $url, string $route, string $role) {
    $user = User::factory()->{$role}()->create();

    $this->actingAs($user)->post(route('logout'))->assertRedirect(route($route));
    $this->assertGuest();
})->with('portal');

test('tidak ada route registrasi atau reset password', function (string $url) {
    $this->get($url)->assertNotFound();
})->with(['/register', '/forgot-password', '/reset-password/token', '/siswa/login']);
