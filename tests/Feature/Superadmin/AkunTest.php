<?php

use App\Enums\Role;
use App\Models\Perusahaan;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->admin = User::factory()->superadmin()->create();
});

test('halaman akun hanya untuk superadmin', function (string $role) {
    $user = User::factory()->{$role}()->create();
    $target = User::factory()->siswa()->create();

    $this->actingAs($user)->get(route('superadmin.akun.index'))->assertForbidden();
    $this->actingAs($user)->get(route('superadmin.akun.edit', $target))->assertForbidden();
    $this->actingAs($user)->post(route('superadmin.akun.store'), [])->assertForbidden();
    $this->actingAs($user)->patch(route('superadmin.akun.status', $target))->assertForbidden();
})->with(['guru', 'industri', 'siswa']);

test('daftar akun bisa difilter per role dan dicari', function () {
    User::factory()->guru()->create(['name' => 'Budi Guru']);
    User::factory()->siswa()->create(['name' => 'Andi Siswa']);

    $this->actingAs($this->admin)
        ->get(route('superadmin.akun.index', ['role' => 'guru']))
        ->assertInertia(fn (Assert $page) => $page
            ->component('superadmin/akun/index')
            ->has('akun.data', 1)
            ->where('akun.data.0.name', 'Budi Guru')
            ->where('jumlah.semua', 2));

    $this->actingAs($this->admin)
        ->get(route('superadmin.akun.index', ['cari' => 'andi']))
        ->assertInertia(fn (Assert $page) => $page->has('akun.data', 1)->where('akun.data.0.name', 'Andi Siswa'));
});

test('daftar akun difilter per program keahlian dan bisa diurutkan', function () {
    $guruTkj = User::factory()->guru('TKJ')->create(['name' => 'Citra Guru']);
    $guruTkj->profilGuru->update(['nip' => '199001']);
    User::factory()->guru('RPL')->create(['name' => 'Eko Guru RPL']);
    $andi = User::factory()->siswa(null, 'TKJ')->create(['name' => 'Andi']);
    $bayu = User::factory()->siswa(null, 'TKJ')->create(['name' => 'Bayu']);
    $andi->profilSiswa->update(['id_siswa' => '2002']);
    $bayu->profilSiswa->update(['id_siswa' => '1001']);
    User::factory()->industri()->create(['name' => 'Dodi Industri']);

    $nama = fn (array $query) => $this->actingAs($this->admin)
        ->get(route('superadmin.akun.index', $query))
        ->viewData('page')['props']['akun']['data'];

    // Jurusan TKJ: guru & siswa TKJ saja (industri tidak punya jurusan).
    expect(array_column($nama(['program' => 'TKJ']), 'name'))->toBe(['Andi', 'Bayu', 'Citra Guru'])
        ->and(array_column($nama(['program' => 'TKJ', 'role' => 'guru']), 'name'))->toBe(['Citra Guru'])
        ->and(array_column($nama(['urut' => 'nama_desc']), 'name'))->toBe(['Eko Guru RPL', 'Dodi Industri', 'Citra Guru', 'Bayu', 'Andi'])
        // Siswa urut NIS, lalu guru urut NIP.
        ->and(array_column($nama(['urut' => 'nomor', 'program' => 'TKJ']), 'name'))->toBe(['Bayu', 'Andi', 'Citra Guru'])
        // Nilai tidak dikenal kembali ke bawaan (nama A–Z), tidak error.
        ->and(array_column($nama(['urut' => 'drop table', 'program' => 'XYZ']), 'name'))->toBe(['Andi', 'Bayu', 'Citra Guru', 'Dodi Industri', 'Eko Guru RPL']);

    $this->actingAs($this->admin)->get(route('superadmin.akun.index', ['cari' => '199001']))
        ->assertInertia(fn (Assert $page) => $page->has('akun.data', 1)->where('akun.data.0.name', 'Citra Guru'));
});

test('akun superadmin tidak tampil di daftar', function () {
    $this->actingAs($this->admin)
        ->get(route('superadmin.akun.index'))
        ->assertInertia(fn (Assert $page) => $page->has('akun.data', 0));
});

test('superadmin membuat akun siswa beserta profilnya', function () {
    $this->actingAs($this->admin)->post(route('superadmin.akun.store'), [
        'role' => 'siswa',
        'name' => 'Andi Pratama',
        'email' => 'andi@sekolah.test',
        'password' => 'rahasia123',
        'id_siswa' => '232410001',
        'program_keahlian' => 'TKJ',
    ])->assertRedirect(route('superadmin.akun.index', ['role' => 'siswa']));

    $siswa = User::where('email', 'andi@sekolah.test')->firstOrFail();

    expect($siswa->role)->toBe(Role::Siswa)
        ->and($siswa->status_aktif)->toBeTrue()
        ->and(Hash::check('rahasia123', $siswa->password))->toBeTrue()
        ->and($siswa->profilSiswa->id_siswa)->toBe('232410001')
        ->and($siswa->profilSiswa->program_keahlian)->toBe('TKJ')
        ->and($siswa->profilSiswa->kelompok_id)->toBeNull();
});

test('superadmin membuat akun guru dan industri', function () {
    $perusahaan = Perusahaan::factory()->create();

    $this->actingAs($this->admin)->post(route('superadmin.akun.store'), [
        'role' => 'guru',
        'name' => 'Sri Wahyuni',
        'email' => 'sri@sekolah.test',
        'password' => 'rahasia123',
        'nip' => '19900218',
        'program_keahlian' => 'RPL',
    ])->assertSessionHasNoErrors();

    $this->actingAs($this->admin)->post(route('superadmin.akun.store'), [
        'role' => 'industri',
        'name' => 'Hendra',
        'email' => 'hendra@pt.test',
        'password' => 'rahasia123',
        'perusahaan_id' => $perusahaan->id,
        'jabatan' => 'Supervisor',
    ])->assertSessionHasNoErrors();

    expect(User::where('email', 'sri@sekolah.test')->first()->profilGuru->program_keahlian)->toBe('RPL')
        ->and(User::where('email', 'hendra@pt.test')->first()->profilIndustri->perusahaan_id)->toBe($perusahaan->id);
});

test('validasi akun: email & NIS unik, industri wajib perusahaan, tidak bisa membuat superadmin', function () {
    $ada = User::factory()->siswa()->create(['email' => 'ada@sekolah.test']);

    $this->actingAs($this->admin)->post(route('superadmin.akun.store'), [
        'role' => 'siswa',
        'name' => 'Siswa Baru',
        'email' => 'ada@sekolah.test',
        'password' => 'rahasia123',
        'id_siswa' => $ada->profilSiswa->id_siswa,
        'program_keahlian' => 'XYZ',
    ])->assertSessionHasErrors(['email', 'id_siswa', 'program_keahlian']);

    $this->actingAs($this->admin)->post(route('superadmin.akun.store'), [
        'role' => 'industri',
        'name' => 'Tanpa Perusahaan',
        'email' => 'industri@pt.test',
        'password' => 'rahasia123',
    ])->assertSessionHasErrors('perusahaan_id');

    $this->actingAs($this->admin)->post(route('superadmin.akun.store'), [
        'role' => 'superadmin',
        'name' => 'Admin Lain',
        'email' => 'admin2@sekolah.test',
        'password' => 'rahasia123',
    ])->assertSessionHasErrors('role');

    expect(User::where('email', 'admin2@sekolah.test')->exists())->toBeFalse();
});

test('edit akun tanpa mengganti kata sandi dan role tidak bisa diubah', function () {
    $guru = User::factory()->guru('TKJ')->create(['password' => 'lama12345']);

    $this->actingAs($this->admin)->put(route('superadmin.akun.update', $guru), [
        'name' => 'Nama Baru',
        'email' => $guru->email,
        'password' => '',
        'program_keahlian' => 'RPL',
    ])->assertSessionHasNoErrors();

    $guru->refresh();

    expect($guru->name)->toBe('Nama Baru')
        ->and(Hash::check('lama12345', $guru->password))->toBeTrue()
        ->and($guru->profilGuru->program_keahlian)->toBe('RPL');

    $this->actingAs($this->admin)->put(route('superadmin.akun.update', $guru), [
        'name' => 'Nama Baru',
        'email' => $guru->email,
        'role' => 'siswa',
    ])->assertSessionHasErrors('role');

    expect($guru->fresh()->role)->toBe(Role::Guru);
});

test('akun superadmin tidak bisa diedit dari panel', function () {
    $adminLain = User::factory()->superadmin()->create();

    $this->actingAs($this->admin)->get(route('superadmin.akun.edit', $adminLain))->assertForbidden();
    $this->actingAs($this->admin)->patch(route('superadmin.akun.status', $adminLain))->assertForbidden();
});

test('superadmin menonaktifkan lalu mengaktifkan kembali akun', function () {
    $siswa = User::factory()->siswa()->create();

    $this->actingAs($this->admin)->patch(route('superadmin.akun.status', $siswa))->assertRedirect();
    expect($siswa->fresh()->status_aktif)->toBeFalse();

    $this->post(route('logout'));
    $this->post(route('login.store'), ['email' => $siswa->email, 'password' => 'password'])
        ->assertSessionHasErrors('email');

    $this->actingAs($this->admin)->patch(route('superadmin.akun.status', $siswa));
    expect($siswa->fresh()->status_aktif)->toBeTrue();
});
