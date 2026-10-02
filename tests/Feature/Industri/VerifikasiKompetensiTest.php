<?php

use App\Enums\StatusKompetensi;
use App\Models\KelompokMagang;
use App\Models\Kompetensi;
use App\Models\Perusahaan;
use App\Models\ProgresKompetensi;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->perusahaan = Perusahaan::factory()->create();
    $this->industri = User::factory()->industri($this->perusahaan)->create();
    $this->industriLain = User::factory()->industri()->create();

    $this->kelompokA = KelompokMagang::factory()->create(['perusahaan_id' => $this->perusahaan->id]);
    $this->kelompokB = KelompokMagang::factory()->create(['perusahaan_id' => $this->perusahaan->id]);
    $this->siswaA = User::factory()->siswa($this->kelompokA, 'TKJ', 'NOC')->create(['name' => 'Andi']);
    $this->siswaB = User::factory()->siswa($this->kelompokB, 'TKJ', 'NOC')->create(['name' => 'Bayu']);
    $this->siswaLuar = User::factory()->siswa(KelompokMagang::factory()->create(), 'TKJ')->create();

    $this->kompetensi = Kompetensi::factory()->create(['program_keahlian' => 'TKJ', 'target_level' => 3]);
    $this->progres = ProgresKompetensi::create([
        'siswa_id' => $this->siswaA->id,
        'kompetensi_id' => $this->kompetensi->id,
        'level_siswa' => 3,
        'status' => StatusKompetensi::MenungguVerifikasi,
    ]);
});

test('industri melihat siswa dari semua kelompok di perusahaannya saja', function () {
    $this->actingAs($this->industri)
        ->get(route('industri.dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('industri/dashboard')
            ->has('siswa', 2)
            ->has('kelompok', 2)
            ->where('menungguVerifikasi', 1));

    $this->actingAs($this->industri)->get(route('industri.siswa.show', $this->siswaA))->assertOk();
    $this->actingAs($this->industri)->get(route('industri.siswa.show', $this->siswaLuar))->assertForbidden();
});

test('industri memverifikasi kompetensi yang menunggu verifikasi', function () {
    $this->actingAs($this->industri)
        ->post(route('industri.siswa.verifikasi', [$this->siswaA, $this->kompetensi]))
        ->assertRedirect();

    $this->progres->refresh();
    expect($this->progres->status)->toBe(StatusKompetensi::Terverifikasi)
        ->and($this->progres->diverifikasi_oleh)->toBe($this->industri->id)
        ->and($this->progres->tanggal_verifikasi)->not->toBeNull();

    // Sudah terverifikasi: tidak bisa diverifikasi lagi.
    $this->actingAs($this->industri)
        ->post(route('industri.siswa.verifikasi', [$this->siswaA, $this->kompetensi]))
        ->assertForbidden();
});

test('verifikasi ditolak jika belum menunggu verifikasi, siswa perusahaan lain, atau bukan industri', function () {
    $this->progres->update(['status' => StatusKompetensi::SedangDipraktikkan]);
    $this->actingAs($this->industri)
        ->post(route('industri.siswa.verifikasi', [$this->siswaA, $this->kompetensi]))
        ->assertForbidden();

    $this->progres->update(['status' => StatusKompetensi::MenungguVerifikasi]);
    $this->actingAs($this->industriLain)
        ->post(route('industri.siswa.verifikasi', [$this->siswaA, $this->kompetensi]))
        ->assertForbidden();

    $guru = User::factory()->guru()->create();
    $this->actingAs($guru)
        ->post(route('industri.siswa.verifikasi', [$this->siswaA, $this->kompetensi]))
        ->assertForbidden();

    // Belum punya progres sama sekali.
    $this->actingAs($this->industri)
        ->post(route('industri.siswa.verifikasi', [$this->siswaB, $this->kompetensi]))
        ->assertNotFound();

    expect($this->progres->fresh()->status)->toBe(StatusKompetensi::MenungguVerifikasi);
});

test('guru melihat status terverifikasi tetapi tidak punya tombol verifikasi', function () {
    $this->progres->update([
        'status' => StatusKompetensi::Terverifikasi,
        'diverifikasi_oleh' => $this->industri->id,
        'tanggal_verifikasi' => now(),
    ]);

    $guru = User::find($this->kelompokA->guru_pembimbing_id);

    $this->actingAs($guru)
        ->get(route('guru.siswa.show', $this->siswaA))
        ->assertInertia(fn (Assert $page) => $page
            ->where('kompetensi.0.status', 'terverifikasi')
            ->where('kompetensi.0.diverifikasi_oleh', $this->industri->name));
});
