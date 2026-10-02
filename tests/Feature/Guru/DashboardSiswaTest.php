<?php

use App\Models\KelompokMagang;
use App\Models\Kompetensi;
use App\Models\ProgresKompetensi;
use App\Models\User;
use Illuminate\Support\Facades\Route;
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

test('ringkasan: level awal 1 sehingga gap dihitung', function () {
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

test('guru tidak punya route untuk mengubah level siswa', function () {
    expect(Route::has('guru.siswa.level'))->toBeFalse();
});

test('kartu siswa menampilkan kompetensi utama dan yang paling dikuasai', function () {
    siswaSiap($this->siswaA, $this->k2->id);
    ProgresKompetensi::create(['siswa_id' => $this->siswaA->id, 'kompetensi_id' => $this->k1->id, 'level_siswa' => 3, 'tanggal_level_diisi' => now()]);
    ProgresKompetensi::create(['siswa_id' => $this->siswaA->id, 'kompetensi_id' => $this->k2->id, 'level_siswa' => 2, 'tanggal_level_diisi' => now()]);

    $this->actingAs($this->guru)
        ->get(route('guru.dashboard', ['kelompok' => $this->kelompokA->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('siswa.0.kompetensi_utama', $this->k2->nama_kompetensi_sekolah)
            ->where('siswa.0.paling_dikuasai.nama', $this->k1->nama_kompetensi_sekolah)
            ->where('siswa.0.paling_dikuasai.level', 3));

    // Belum ada yang naik level -> null.
    $this->actingAs($this->guru)
        ->get(route('guru.dashboard', ['kelompok' => $this->kelompokB->id]))
        ->assertInertia(fn (Assert $page) => $page->where('siswa.0.paling_dikuasai', null));
});

test('rekap kelompok berisi learning gap & progres semua siswa kelompok yang dibimbing saja', function () {
    $this->actingAs($this->guru)
        ->get(route('guru.rekap', ['kelompok' => $this->kelompokA->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->component('guru/rekap')
            ->has('kelompok', 2)
            ->has('program.0.kompetensi', 2)
            ->has('program.0.siswa', 1)
            ->where('program.0.siswa.0.nama', 'Andi')
            ->where("program.0.siswa.0.sel.{$this->k1->id}.gap", 2));

    // Kelompok guru lain lewat URL diabaikan.
    $this->actingAs($this->guru)
        ->get(route('guru.rekap', ['kelompok' => $this->kelompokLain->id]))
        ->assertInertia(fn (Assert $page) => $page->whereNot('kelompokTerpilih', $this->kelompokLain->id));

    $this->actingAs(User::factory()->industri()->create())->get(route('guru.rekap'))->assertForbidden();
});
