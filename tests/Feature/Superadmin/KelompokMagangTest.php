<?php

use App\Models\KelompokMagang;
use App\Models\Perusahaan;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->admin = User::factory()->superadmin()->create();
    $this->perusahaan = Perusahaan::factory()->create();
    $this->guru = User::factory()->guru()->create();
});

function dataKelompok(array $timpa = []): array
{
    return [
        'nama_kelompok' => 'PKL TKJ 2026',
        'perusahaan_id' => test()->perusahaan->id,
        'guru_pembimbing_id' => test()->guru->id,
        'periode_mulai' => '2026-09-14',
        'periode_selesai' => '2026-12-11',
        'siswa_ids' => [],
        ...$timpa,
    ];
}

test('halaman kelompok hanya untuk superadmin', function (string $role) {
    $user = User::factory()->{$role}()->create();

    $this->actingAs($user)->get(route('superadmin.kelompok.index'))->assertForbidden();
    $this->actingAs($user)->post(route('superadmin.kelompok.store'), dataKelompok())->assertForbidden();
})->with(['guru', 'industri', 'siswa']);

test('superadmin membuat kelompok dengan guru dan siswa', function () {
    $siswa = User::factory()->count(2)->siswa()->create();

    $this->actingAs($this->admin)
        ->post(route('superadmin.kelompok.store'), dataKelompok(['siswa_ids' => $siswa->pluck('id')->all()]))
        ->assertRedirect(route('superadmin.kelompok.index'));

    $kelompok = KelompokMagang::firstOrFail();

    expect($kelompok->guru_pembimbing_id)->toBe($this->guru->id)
        ->and($kelompok->siswa()->pluck('users.id')->sort()->values()->all())->toBe($siswa->pluck('id')->sort()->values()->all());
});

test('validasi kelompok: guru harus akun guru, periode selesai tidak sebelum mulai, siswa harus akun siswa', function () {
    $bukanGuru = User::factory()->industri()->create();
    $bukanSiswa = User::factory()->guru()->create();

    $this->actingAs($this->admin)->post(route('superadmin.kelompok.store'), dataKelompok([
        'guru_pembimbing_id' => $bukanGuru->id,
        'periode_selesai' => '2026-09-01',
        'siswa_ids' => [$bukanSiswa->id],
    ]))->assertSessionHasErrors(['guru_pembimbing_id', 'periode_selesai', 'siswa_ids.0']);
});

test('satu guru boleh membimbing beberapa kelompok', function () {
    $this->actingAs($this->admin)->post(route('superadmin.kelompok.store'), dataKelompok(['nama_kelompok' => 'A']));
    $this->actingAs($this->admin)->post(route('superadmin.kelompok.store'), dataKelompok(['nama_kelompok' => 'B']));

    expect($this->guru->kelompokDibimbing()->count())->toBe(2);
});

test('memindahkan siswa ke kelompok di perusahaan lain mengosongkan unit kerjanya', function () {
    $kelompokLama = KelompokMagang::factory()->create();
    $kelompokSamaPerusahaan = KelompokMagang::factory()->create(['perusahaan_id' => $kelompokLama->perusahaan_id]);
    $kelompokBaru = KelompokMagang::factory()->create(['perusahaan_id' => $this->perusahaan->id]);

    $pindahPerusahaan = User::factory()->siswa($kelompokLama, 'TKJ', 'Jaringan')->create();
    $pindahSamaPerusahaan = User::factory()->siswa($kelompokLama, 'TKJ', 'Helpdesk')->create();

    $this->actingAs($this->admin)->put(
        route('superadmin.kelompok.update', $kelompokBaru),
        dataKelompok(['siswa_ids' => [$pindahPerusahaan->id]]),
    )->assertSessionHasNoErrors();

    $this->actingAs($this->admin)->put(route('superadmin.kelompok.update', $kelompokSamaPerusahaan), [
        ...dataKelompok(['siswa_ids' => [$pindahSamaPerusahaan->id]]),
        'perusahaan_id' => $kelompokLama->perusahaan_id,
    ])->assertSessionHasNoErrors();

    expect($pindahPerusahaan->profilSiswa->fresh()->kelompok_id)->toBe($kelompokBaru->id)
        ->and($pindahPerusahaan->profilSiswa->fresh()->unit_kerja)->toBeNull()
        ->and($pindahSamaPerusahaan->profilSiswa->fresh()->kelompok_id)->toBe($kelompokSamaPerusahaan->id)
        ->and($pindahSamaPerusahaan->profilSiswa->fresh()->unit_kerja)->toBe('Helpdesk');
});

test('siswa yang dikeluarkan dari kelompok kembali tanpa kelompok', function () {
    $kelompok = KelompokMagang::factory()->create(['perusahaan_id' => $this->perusahaan->id]);
    $tetap = User::factory()->siswa($kelompok, 'TKJ', 'Jaringan')->create();
    $keluar = User::factory()->siswa($kelompok, 'TKJ', 'Jaringan')->create();

    $this->actingAs($this->admin)->put(
        route('superadmin.kelompok.update', $kelompok),
        dataKelompok(['siswa_ids' => [$tetap->id]]),
    )->assertSessionHasNoErrors();

    expect($tetap->profilSiswa->fresh()->unit_kerja)->toBe('Jaringan')
        ->and($keluar->profilSiswa->fresh()->kelompok_id)->toBeNull()
        ->and($keluar->profilSiswa->fresh()->unit_kerja)->toBeNull();
});

test('mengganti perusahaan kelompok membuat semua siswanya memilih unit kerja lagi', function () {
    $kelompok = KelompokMagang::factory()->create();
    $siswa = User::factory()->siswa($kelompok, 'TKJ', 'Jaringan')->create();

    $this->actingAs($this->admin)->put(
        route('superadmin.kelompok.update', $kelompok),
        dataKelompok(['siswa_ids' => [$siswa->id]]),
    )->assertSessionHasNoErrors();

    expect($kelompok->fresh()->perusahaan_id)->toBe($this->perusahaan->id)
        ->and($siswa->profilSiswa->fresh()->unit_kerja)->toBeNull();
});

test('form kelompok menampilkan siswa beserta kelompoknya saat ini', function () {
    $kelompokLain = KelompokMagang::factory()->create(['nama_kelompok' => 'Kelompok Lain']);
    User::factory()->siswa($kelompokLain)->create();
    User::factory()->siswa()->nonaktif()->create();

    $this->actingAs($this->admin)
        ->get(route('superadmin.kelompok.create'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('superadmin/kelompok/form')
            ->has('siswa', 1)
            ->where('siswa.0.kelompok_nama', 'Kelompok Lain'));
});
