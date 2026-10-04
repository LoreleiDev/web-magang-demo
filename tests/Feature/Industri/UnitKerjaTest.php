<?php

use App\Models\KelompokMagang;
use App\Models\Kompetensi;
use App\Models\Perusahaan;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

/*
| Pembimbing industri menambah unit kerja perusahaannya sendiri (revisi 4 Okt 2026).
*/

beforeEach(function () {
    $this->perusahaan = Perusahaan::factory()->create(['daftar_unit_kerja' => ['Jaringan', 'Helpdesk']]);
    $this->industri = User::factory()->industri($this->perusahaan)->create();
    $this->kelompok = KelompokMagang::factory()->create(['perusahaan_id' => $this->perusahaan->id]);
});

test('industri melihat unit kerja perusahaannya beserta jumlah siswa', function () {
    User::factory()->siswa($this->kelompok, 'TKJ', 'Jaringan')->create();

    $this->actingAs($this->industri)
        ->get(route('industri.unit-kerja.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('industri/unit-kerja')
            ->where('perusahaan', $this->perusahaan->nama)
            ->where('unitKerja', [
                ['nama' => 'Jaringan', 'jumlah_siswa' => 1],
                ['nama' => 'Helpdesk', 'jumlah_siswa' => 0],
            ]));
});

test('industri menambah unit kerja dan siswa langsung bisa memilihnya', function () {
    $this->actingAs($this->industri)
        ->post(route('industri.unit-kerja.store'), ['nama' => '  Gudang Perangkat  '])
        ->assertRedirect(route('industri.unit-kerja.index'))
        ->assertSessionHasNoErrors();

    expect($this->perusahaan->fresh()->daftar_unit_kerja)->toBe(['Jaringan', 'Helpdesk', 'Gudang Perangkat']);

    $siswa = siswaSiap(
        User::factory()->siswa($this->kelompok, 'TKJ', 'Jaringan')->create(),
        Kompetensi::factory()->create(['program_keahlian' => 'TKJ'])->id,
    );
    $this->actingAs($siswa)
        ->put(route('siswa.unit-kerja'), ['unit_kerja' => 'Gudang Perangkat'])
        ->assertSessionHasNoErrors();
    expect($siswa->profilSiswa->fresh()->unit_kerja)->toBe('Gudang Perangkat');
});

test('unit kerja kembar (tanpa beda huruf besar/kecil), kosong, atau terlalu banyak ditolak', function () {
    $this->actingAs($this->industri)
        ->post(route('industri.unit-kerja.store'), ['nama' => 'jaringan'])
        ->assertSessionHasErrors(['nama' => 'Unit kerja "jaringan" sudah ada.']);
    $this->actingAs($this->industri)
        ->post(route('industri.unit-kerja.store'), ['nama' => '   '])
        ->assertSessionHasErrors('nama');

    $this->perusahaan->update(['daftar_unit_kerja' => array_map(fn ($i) => "Unit {$i}", range(1, 30))]);
    $this->actingAs($this->industri)
        ->post(route('industri.unit-kerja.store'), ['nama' => 'Unit baru'])
        ->assertSessionHasErrors(['nama' => 'Maksimal 30 unit kerja per perusahaan.']);

    expect($this->perusahaan->fresh()->daftar_unit_kerja)->toHaveCount(30);
});

test('hanya perusahaan sendiri yang berubah; role lain tidak bisa menambah', function () {
    $lain = Perusahaan::factory()->create(['daftar_unit_kerja' => ['Produksi']]);

    // Perusahaan diambil dari akun industri, jadi parameter perusahaan lain diabaikan.
    $this->actingAs($this->industri)
        ->post(route('industri.unit-kerja.store'), ['nama' => 'Baru', 'perusahaan_id' => $lain->id]);

    expect($lain->fresh()->daftar_unit_kerja)->toBe(['Produksi'])
        ->and($this->perusahaan->fresh()->daftar_unit_kerja)->toContain('Baru');

    foreach ([User::factory()->guru()->create(), User::factory()->siswa($this->kelompok)->create(), User::factory()->superadmin()->create()] as $user) {
        $this->actingAs($user)->post(route('industri.unit-kerja.store'), ['nama' => 'Tidak boleh'])->assertForbidden();
    }

    expect($this->perusahaan->fresh()->daftar_unit_kerja)->not->toContain('Tidak boleh');
});

test('industri menghapus unit kerja yang tidak dipakai siswa', function () {
    $this->actingAs($this->industri)
        ->delete(route('industri.unit-kerja.destroy'), ['nama' => 'Helpdesk'])
        ->assertRedirect(route('industri.unit-kerja.index'))
        ->assertSessionHasNoErrors();

    expect($this->perusahaan->fresh()->daftar_unit_kerja)->toBe(['Jaringan']);
});

test('unit kerja yang masih dipilih siswa atau unit satu-satunya tidak bisa dihapus', function () {
    User::factory()->siswa($this->kelompok, 'TKJ', 'Jaringan')->create();

    $this->actingAs($this->industri)
        ->delete(route('industri.unit-kerja.destroy'), ['nama' => 'Jaringan'])
        ->assertSessionHasErrors(['hapus' => 'Unit kerja "Jaringan" masih dipilih 1 siswa sehingga belum bisa dihapus.']);

    // Siswa di perusahaan LAIN dengan nama unit sama tidak menghalangi.
    $this->perusahaan->update(['daftar_unit_kerja' => ['Jaringan', 'Helpdesk', 'Gudang']]);
    User::factory()->siswa(KelompokMagang::factory()->create(), 'TKJ', 'Gudang')->create();
    $this->actingAs($this->industri)
        ->delete(route('industri.unit-kerja.destroy'), ['nama' => 'Gudang'])
        ->assertSessionHasNoErrors();

    $this->perusahaan->update(['daftar_unit_kerja' => ['Helpdesk']]);
    $this->actingAs($this->industri)
        ->delete(route('industri.unit-kerja.destroy'), ['nama' => 'Helpdesk'])
        ->assertSessionHasErrors(['hapus' => 'Perusahaan harus punya minimal satu unit kerja.']);

    $this->actingAs($this->industri)
        ->delete(route('industri.unit-kerja.destroy'), ['nama' => 'Tidak ada'])
        ->assertSessionHasErrors('hapus');

    expect($this->perusahaan->fresh()->daftar_unit_kerja)->toBe(['Helpdesk']);
});

test('industri tidak bisa menghapus unit perusahaan lain; role lain ditolak', function () {
    $lain = Perusahaan::factory()->create(['daftar_unit_kerja' => ['Produksi', 'Gudang']]);

    $this->actingAs($this->industri)
        ->delete(route('industri.unit-kerja.destroy'), ['nama' => 'Produksi', 'perusahaan_id' => $lain->id])
        ->assertSessionHasErrors('hapus');

    foreach ([User::factory()->guru()->create(), User::factory()->siswa($this->kelompok)->create(), User::factory()->superadmin()->create()] as $user) {
        $this->actingAs($user)->delete(route('industri.unit-kerja.destroy'), ['nama' => 'Helpdesk'])->assertForbidden();
    }

    expect($lain->fresh()->daftar_unit_kerja)->toBe(['Produksi', 'Gudang'])
        ->and($this->perusahaan->fresh()->daftar_unit_kerja)->toBe(['Jaringan', 'Helpdesk']);
});

test('industri mengubah unit kerja siswa di perusahaannya', function () {
    $siswa = User::factory()->siswa($this->kelompok, 'TKJ', 'Jaringan')->create(['name' => 'Andi']);

    $this->actingAs($this->industri)
        ->get(route('industri.siswa.show', $siswa))
        ->assertInertia(fn (Assert $page) => $page->where('daftarUnitKerja', ['Jaringan', 'Helpdesk']));

    $this->actingAs($this->industri)
        ->put(route('industri.siswa.unit-kerja', $siswa), ['unit_kerja' => 'Helpdesk'])
        ->assertSessionHasNoErrors();
    expect($siswa->profilSiswa->fresh()->unit_kerja)->toBe('Helpdesk');

    // Hanya unit dari daftar perusahaan.
    $this->actingAs($this->industri)
        ->put(route('industri.siswa.unit-kerja', $siswa), ['unit_kerja' => 'Produksi'])
        ->assertSessionHasErrors(['unit_kerja' => 'Pilih unit kerja dari daftar perusahaan.']);
    expect($siswa->profilSiswa->fresh()->unit_kerja)->toBe('Helpdesk');
});

test('industri tidak bisa mengubah unit kerja siswa perusahaan lain; role lain ditolak', function () {
    $siswaLain = User::factory()->siswa(KelompokMagang::factory()->create(), 'TKJ', 'Jaringan')->create();
    $tanpaKelompok = User::factory()->siswa(null, 'TKJ')->create();
    $siswa = User::factory()->siswa($this->kelompok, 'TKJ', 'Jaringan')->create();

    foreach ([$siswaLain, $tanpaKelompok] as $target) {
        $this->actingAs($this->industri)
            ->put(route('industri.siswa.unit-kerja', $target), ['unit_kerja' => 'Jaringan'])
            ->assertForbidden();
    }

    $guruPembimbing = User::find($this->kelompok->guru_pembimbing_id);
    foreach ([$guruPembimbing, $siswa, User::factory()->superadmin()->create()] as $user) {
        $this->actingAs($user)
            ->put(route('industri.siswa.unit-kerja', $siswa), ['unit_kerja' => 'Helpdesk'])
            ->assertForbidden();
    }

    expect($siswa->profilSiswa->fresh()->unit_kerja)->toBe('Jaringan')
        ->and($siswaLain->profilSiswa->fresh()->unit_kerja)->toBe('Jaringan');
});
