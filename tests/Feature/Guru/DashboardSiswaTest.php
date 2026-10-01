<?php

use App\Enums\StatusKompetensi;
use App\Models\KelompokMagang;
use App\Models\Kompetensi;
use App\Models\ProgresKompetensi;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->guru = User::factory()->guru('TKJ')->create();
    $this->guruLain = User::factory()->guru('TKJ')->create();

    $this->kelompokA = KelompokMagang::factory()->create(['guru_pembimbing_id' => $this->guru->id, 'nama_kelompok' => 'A']);
    $this->kelompokB = KelompokMagang::factory()->create(['guru_pembimbing_id' => $this->guru->id, 'nama_kelompok' => 'B']);
    $this->kelompokLain = KelompokMagang::factory()->create(['guru_pembimbing_id' => $this->guruLain->id]);

    $this->siswaA = User::factory()->siswa($this->kelompokA, 'TKJ', 'NOC')->create(['name' => 'Andi']);
    $this->siswaB = User::factory()->siswa($this->kelompokB, 'TKJ')->create(['name' => 'Budi']);
    $this->siswaLain = User::factory()->siswa($this->kelompokLain, 'TKJ')->create();

    $this->k1 = Kompetensi::factory()->create(['program_keahlian' => 'TKJ', 'target_level' => 3, 'dibuat_oleh' => $this->guru->id]);
    $this->k2 = Kompetensi::factory()->create(['program_keahlian' => 'TKJ', 'target_level' => 2, 'dibuat_oleh' => $this->guru->id]);
});

test('guru hanya melihat kelompok yang ia bimbing, dan bisa berpindah kelompok', function () {
    $this->actingAs($this->guru)
        ->get(route('guru.dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('guru/dashboard')
            ->has('kelompok', 2)
            ->has('siswa', 1));

    $this->actingAs($this->guru)
        ->get(route('guru.dashboard', ['kelompok' => $this->kelompokB->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('kelompokTerpilih', $this->kelompokB->id)
            ->where('siswa.0.nama', 'Budi'));
});

test('kelompok guru lain lewat URL diabaikan', function () {
    $this->actingAs($this->guru)
        ->get(route('guru.dashboard', ['kelompok' => $this->kelompokLain->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->whereNot('kelompokTerpilih', $this->kelompokLain->id)
            ->where('siswa', fn ($siswa) => collect($siswa)->doesntContain('id', $this->siswaLain->id)));
});

test('ringkasan: level kosong dianggap 1 sehingga gap dihitung', function () {
    // k1 target 3, belum diisi (level 1) -> gap 2. k2 target 2, diisi 2 -> sesuai.
    ProgresKompetensi::create(['siswa_id' => $this->siswaA->id, 'kompetensi_id' => $this->k2->id, 'level_siswa' => 2]);

    $this->actingAs($this->guru)
        ->get(route('guru.siswa.show', $this->siswaA))
        ->assertInertia(fn (Assert $page) => $page
            ->component('guru/siswa')
            ->where('siswa.progres.total', 2)
            ->where('siswa.progres.dikuasai', 1)
            ->where('siswa.progres.gap', 1)
            ->where('siswa.progres.persen', 50)
            ->where('kompetensi.0.level', 1)
            ->where('kompetensi.0.level_diisi', false)
            ->where('kompetensi.0.gap', 2)
            ->where('kompetensi.0.warna', 'tinggi')
            ->where('kompetensi.1.warna', 'aman'));
});

test('guru tidak bisa membuka siswa di luar kelompoknya', function () {
    $this->actingAs($this->guru)->get(route('guru.siswa.show', $this->siswaLain))->assertForbidden();
    $this->actingAs($this->guru)->get(route('guru.siswa.show', $this->guruLain))->assertForbidden();
});

test('guru mengisi level dan status berpindah otomatis', function () {
    $isi = fn (int $level) => $this->actingAs($this->guru)
        ->put(route('guru.siswa.level', [$this->siswaA, $this->k1]), ['level_siswa' => $level]);

    $isi(2)->assertSessionHasNoErrors();
    $progres = ProgresKompetensi::where('siswa_id', $this->siswaA->id)->where('kompetensi_id', $this->k1->id)->firstOrFail();

    expect($progres->level_siswa)->toBe(2)
        ->and($progres->level_diisi_oleh)->toBe($this->guru->id)
        ->and($progres->tanggal_level_diisi)->not->toBeNull()
        ->and($progres->status)->toBe(StatusKompetensi::BelumDipelajari);

    $isi(3);
    expect($progres->fresh()->status)->toBe(StatusKompetensi::MenungguVerifikasi);

    // Aturan sementara: turun di bawah target sebelum diverifikasi -> kembali dipraktikkan.
    $isi(2);
    expect($progres->fresh()->status)->toBe(StatusKompetensi::SedangDipraktikkan);

    // Yang sudah terverifikasi tidak diubah guru.
    $progres->update(['status' => StatusKompetensi::Terverifikasi]);
    $isi(1);
    expect($progres->fresh()->status)->toBe(StatusKompetensi::Terverifikasi);
});

test('isi level ditolak untuk siswa lain, level di luar 1-4, atau kompetensi program lain', function () {
    $this->actingAs($this->guru)
        ->put(route('guru.siswa.level', [$this->siswaLain, $this->k1]), ['level_siswa' => 3])
        ->assertForbidden();

    $this->actingAs($this->guru)
        ->put(route('guru.siswa.level', [$this->siswaA, $this->k1]), ['level_siswa' => 5])
        ->assertSessionHasErrors('level_siswa');

    $kompetensiRpl = Kompetensi::factory()->create(['program_keahlian' => 'RPL']);
    $this->actingAs($this->guru)
        ->put(route('guru.siswa.level', [$this->siswaA, $kompetensiRpl]), ['level_siswa' => 3])
        ->assertNotFound();

    $industri = User::factory()->industri($this->kelompokA->perusahaan)->create();
    $this->actingAs($industri)
        ->put(route('guru.siswa.level', [$this->siswaA, $this->k1]), ['level_siswa' => 3])
        ->assertForbidden();

    expect(ProgresKompetensi::count())->toBe(0);
});
