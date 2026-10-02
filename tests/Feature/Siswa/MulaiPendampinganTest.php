<?php

use App\Models\KelompokMagang;
use App\Models\Kompetensi;
use App\Models\Perusahaan;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->perusahaan = Perusahaan::factory()->create(['daftar_unit_kerja' => ['NOC', 'Helpdesk']]);
    $this->kelompok = KelompokMagang::factory()->create(['perusahaan_id' => $this->perusahaan->id]);
    $this->siswa = User::factory()->siswa($this->kelompok, 'TKJ')->create();
    $this->kompetensi = Kompetensi::factory()->create(['program_keahlian' => 'TKJ']);
});

test('halaman siswa lain diarahkan ke Mulai Pendampingan sebelum memilih kompetensi', function (string $url) {
    $this->actingAs($this->siswa)->get($url)->assertRedirect(route('siswa.mulai'));
})->with(['/siswa', '/siswa/belajar', '/siswa/logbook', '/siswa/progress']);

test('Mulai Pendampingan menampilkan data terkunci, unit kerja, dan kompetensi', function () {
    $this->actingAs($this->siswa)
        ->get(route('siswa.mulai'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('siswa/mulai')
            ->where('terdaftar', true)
            ->where('konteks.perusahaan', $this->perusahaan->nama)
            ->where('konteks.id_siswa', $this->siswa->profilSiswa->id_siswa)
            ->where('daftarUnitKerja', ['NOC', 'Helpdesk'])
            ->has('kompetensi', 1));
});

test('pertama kali: unit kerja wajib dari daftar; setelah itu dashboard terbuka', function () {
    $this->actingAs($this->siswa)
        ->post(route('siswa.mulai.store'), ['kompetensi_id' => $this->kompetensi->id])
        ->assertSessionHasErrors('unit_kerja');

    $this->actingAs($this->siswa)
        ->post(route('siswa.mulai.store'), ['unit_kerja' => 'Gudang', 'kompetensi_id' => $this->kompetensi->id])
        ->assertSessionHasErrors('unit_kerja');

    $this->actingAs($this->siswa)
        ->post(route('siswa.mulai.store'), ['unit_kerja' => 'NOC', 'kompetensi_id' => $this->kompetensi->id])
        ->assertRedirect(route('siswa.dashboard'));

    $profil = $this->siswa->profilSiswa->fresh();
    expect($profil->unit_kerja)->toBe('NOC')
        ->and($profil->kompetensi_fokus_id)->toBe($this->kompetensi->id);

    $this->actingAs($this->siswa)->get(route('siswa.dashboard'))->assertOk();
});

test('pilihan disimpan permanen: login berikutnya langsung ke Dashboard tanpa memilih ulang', function () {
    siswaSiap($this->siswa, $this->kompetensi->id);

    $this->actingAs($this->siswa)->get(route('siswa.mulai'))->assertRedirect(route('siswa.dashboard'));

    $this->post(route('logout'));
    $this->post(route('login.store'), ['email' => $this->siswa->email, 'password' => 'password'])
        ->assertRedirect(route('siswa.dashboard'));
    $this->get(route('siswa.dashboard'))->assertOk();
});

test('kompetensi utama diganti dari Peta Kompetensi, progres tidak berubah', function () {
    siswaSiap($this->siswa, $this->kompetensi->id);
    $lain = Kompetensi::factory()->create(['program_keahlian' => 'TKJ']);
    $rpl = Kompetensi::factory()->create(['program_keahlian' => 'RPL']);

    $this->actingAs($this->siswa)->get(route('siswa.peta'))
        ->assertInertia(fn (Assert $page) => $page->where('kompetensiUtamaId', $this->kompetensi->id));

    $this->actingAs($this->siswa)
        ->put(route('siswa.kompetensi-utama'), ['kompetensi_id' => $lain->id])
        ->assertSessionHasNoErrors();
    expect($this->siswa->profilSiswa->fresh()->kompetensi_fokus_id)->toBe($lain->id);

    $this->actingAs($this->siswa)
        ->put(route('siswa.kompetensi-utama'), ['kompetensi_id' => $rpl->id])
        ->assertSessionHasErrors('kompetensi_id');
});

test('unit kerja bisa diganti kapan saja, hanya dari daftar perusahaan', function () {
    siswaSiap($this->siswa, $this->kompetensi->id);
    $this->siswa->profilSiswa->update(['unit_kerja' => 'NOC']);

    $this->actingAs($this->siswa)
        ->put(route('siswa.unit-kerja'), ['unit_kerja' => 'Helpdesk'])
        ->assertSessionHasNoErrors();
    expect($this->siswa->profilSiswa->fresh()->unit_kerja)->toBe('Helpdesk');

    $this->actingAs($this->siswa)
        ->put(route('siswa.unit-kerja'), ['unit_kerja' => 'Gudang'])
        ->assertSessionHasErrors('unit_kerja');
});

test('kompetensi harus dari program keahlian siswa', function () {
    $rpl = Kompetensi::factory()->create(['program_keahlian' => 'RPL']);

    $this->actingAs($this->siswa)
        ->post(route('siswa.mulai.store'), ['unit_kerja' => 'NOC', 'kompetensi_id' => $rpl->id])
        ->assertSessionHasErrors('kompetensi_id');
});

test('siswa yang belum masuk kelompok melihat pesan dan tidak bisa memulai', function () {
    $tanpaKelompok = User::factory()->siswa()->create();

    $this->actingAs($tanpaKelompok)
        ->get(route('siswa.mulai'))
        ->assertInertia(fn (Assert $page) => $page->where('terdaftar', false));

    $this->actingAs($tanpaKelompok)
        ->post(route('siswa.mulai.store'), ['kompetensi_id' => $this->kompetensi->id])
        ->assertForbidden();

    $this->actingAs($tanpaKelompok)
        ->get(route('siswa.dashboard'))
        ->assertRedirect(route('siswa.mulai'));
});
