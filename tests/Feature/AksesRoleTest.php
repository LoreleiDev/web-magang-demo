<?php

use App\Models\User;

$halaman = [
    'superadmin' => '/superadmin',
    'guru' => '/guru',
    'industri' => '/industri',
    'siswa' => '/siswa/mulai',
];

test('setiap role hanya bisa membuka halamannya sendiri lewat URL langsung', function (string $role) use ($halaman) {
    $user = User::factory()->{$role}()->create();

    foreach ($halaman as $pemilik => $url) {
        $respons = $this->actingAs($user)->get($url);

        $pemilik === $role ? $respons->assertOk() : $respons->assertForbidden();
    }
})->with(['superadmin', 'guru', 'industri', 'siswa']);

test('akun yang dinonaktifkan saat sedang login langsung dikeluarkan', function () {
    $user = User::factory()->guru()->create();

    $this->actingAs($user)->get('/guru')->assertOk();

    $user->update(['status_aktif' => false]);

    $this->actingAs($user)->get('/guru')
        ->assertRedirect(route('login.guru'))
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('data user yang dibagikan ke frontend tidak memuat kolom rahasia', function () {
    $user = User::factory()->siswa()->create();

    $this->actingAs($user)->get('/siswa/mulai')
        ->assertInertia(fn ($page) => $page
            ->where('auth.user.id', $user->id)
            ->where('auth.user.role', 'siswa')
            ->missing('auth.user.password')
            ->missing('auth.user.remember_token'));
});
