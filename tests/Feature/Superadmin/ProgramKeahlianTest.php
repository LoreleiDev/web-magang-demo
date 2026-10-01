<?php

use App\Models\Kompetensi;
use App\Models\ProgramKeahlian;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->admin = User::factory()->superadmin()->create();
});

test('hanya superadmin yang mengelola program keahlian', function (string $role) {
    $user = User::factory()->{$role}()->create();

    $this->actingAs($user)->get(route('superadmin.program-keahlian.index'))->assertForbidden();
    $this->actingAs($user)->post(route('superadmin.program-keahlian.store'), ['kode' => 'X', 'nama' => 'X'])->assertForbidden();
})->with(['guru', 'industri', 'siswa']);

test('superadmin menambah program lalu bisa dipakai di akun siswa', function () {
    $this->actingAs($this->admin)
        ->post(route('superadmin.program-keahlian.store'), ['kode' => 'dkv', 'nama' => 'Desain Komunikasi Visual'])
        ->assertSessionHasNoErrors();

    expect(ProgramKeahlian::where('kode', 'DKV')->exists())->toBeTrue();

    $this->actingAs($this->admin)->post(route('superadmin.akun.store'), [
        'role' => 'siswa',
        'name' => 'Siswa DKV',
        'email' => 'dkv@sekolah.test',
        'password' => 'rahasia123',
        'id_siswa' => '99001',
        'program_keahlian' => 'DKV',
    ])->assertSessionHasNoErrors();

    $this->actingAs($this->admin)->post(route('superadmin.akun.store'), [
        'role' => 'siswa',
        'name' => 'Siswa Salah',
        'email' => 'salah@sekolah.test',
        'password' => 'rahasia123',
        'id_siswa' => '99002',
        'program_keahlian' => 'TIDAKADA',
    ])->assertSessionHasErrors('program_keahlian');
});

test('kode program unik dan tidak bisa diubah', function () {
    $tkj = ProgramKeahlian::where('kode', 'TKJ')->firstOrFail();

    $this->actingAs($this->admin)
        ->post(route('superadmin.program-keahlian.store'), ['kode' => 'TKJ', 'nama' => 'Lain'])
        ->assertSessionHasErrors('kode');

    $this->actingAs($this->admin)
        ->put(route('superadmin.program-keahlian.update', $tkj), ['kode' => 'TKJ2', 'nama' => 'Baru'])
        ->assertSessionHasErrors('kode');

    $this->actingAs($this->admin)
        ->put(route('superadmin.program-keahlian.update', $tkj), ['nama' => 'Teknik Jaringan Komputer dan Telekomunikasi'])
        ->assertSessionHasNoErrors();

    expect($tkj->fresh()->nama)->toBe('Teknik Jaringan Komputer dan Telekomunikasi');
});

test('program yang masih dipakai tidak bisa dihapus', function () {
    Kompetensi::factory()->create(['program_keahlian' => 'RPL']);
    $rpl = ProgramKeahlian::where('kode', 'RPL')->firstOrFail();
    $kosong = ProgramKeahlian::create(['kode' => 'AKL', 'nama' => 'Akuntansi']);

    $this->actingAs($this->admin)->delete(route('superadmin.program-keahlian.destroy', $rpl));
    $this->actingAs($this->admin)->delete(route('superadmin.program-keahlian.destroy', $kosong));

    expect($rpl->fresh())->not->toBeNull()
        ->and($kosong->fresh())->toBeNull();

    $this->actingAs($this->admin)
        ->get(route('superadmin.program-keahlian.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('superadmin/program-keahlian')
            ->where('program.1.kode', 'TKJ')
            ->where('program.0.bisa_dihapus', false));
});
