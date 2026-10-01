<?php

use App\Models\Kompetensi;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->guru = User::factory()->guru('TKJ')->create();
});

test('guru menambah kompetensi untuk program keahliannya sendiri', function () {
    $this->actingAs($this->guru)->post(route('guru.kompetensi.store'), [
        'nama_kompetensi_sekolah' => 'Subnetting',
        'aktivitas_kompetensi_industri' => 'Membagi IP pelanggan',
        'target_level' => 3,
        'program_keahlian' => 'RPL',
    ])->assertRedirect(route('guru.kompetensi.index'));

    $k = Kompetensi::firstOrFail();

    expect($k->program_keahlian)->toBe('TKJ')
        ->and($k->dibuat_oleh)->toBe($this->guru->id);
});

test('validasi kompetensi: target level 1-4', function () {
    $this->actingAs($this->guru)->post(route('guru.kompetensi.store'), [
        'nama_kompetensi_sekolah' => '',
        'aktivitas_kompetensi_industri' => '',
        'target_level' => 5,
    ])->assertSessionHasErrors(['nama_kompetensi_sekolah', 'aktivitas_kompetensi_industri', 'target_level']);
});

test('daftar hanya berisi kompetensi program sendiri; kompetensi program lain tidak bisa diedit', function () {
    Kompetensi::factory()->create(['program_keahlian' => 'TKJ']);
    $rpl = Kompetensi::factory()->create(['program_keahlian' => 'RPL']);

    $this->actingAs($this->guru)
        ->get(route('guru.kompetensi.index'))
        ->assertInertia(fn (Assert $page) => $page->has('kompetensi', 1));

    $this->actingAs($this->guru)->get(route('guru.kompetensi.edit', $rpl))->assertForbidden();
    $this->actingAs($this->guru)->put(route('guru.kompetensi.update', $rpl), [
        'nama_kompetensi_sekolah' => 'X',
        'aktivitas_kompetensi_industri' => 'Y',
        'target_level' => 2,
    ])->assertForbidden();
});

test('guru tanpa program keahlian tidak bisa membuat kompetensi', function () {
    $guru = User::factory()->guru(null)->create();

    $this->actingAs($guru)->get(route('guru.kompetensi.create'))->assertForbidden();
    $this->actingAs($guru)
        ->get(route('guru.kompetensi.index'))
        ->assertInertia(fn (Assert $page) => $page->where('program', null));
});

test('role lain tidak bisa membuka menu guru', function (string $role) {
    $user = User::factory()->{$role}()->create();

    foreach (['guru.dashboard', 'guru.kompetensi.index', 'guru.materi.index', 'guru.dokumen.index'] as $route) {
        $this->actingAs($user)->get(route($route))->assertForbidden();
    }
})->with(['superadmin', 'industri', 'siswa']);
