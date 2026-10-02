<?php

use App\Enums\JenisDokumen;
use App\Enums\StatusKompetensi;
use App\Models\DokumenKnowledgeBase;
use App\Models\HasilAssessment;
use App\Models\KelompokMagang;
use App\Models\Kompetensi;
use App\Models\Kuis;
use App\Models\Logbook;
use App\Models\Materi;
use App\Models\Perusahaan;
use App\Models\ProgresKompetensi;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

/**
 * Dua dunia terpisah: perusahaan A (siswa TKJ, guru A) dan perusahaan B (siswa RPL, guru B).
 * Menguji matriks hak akses CLAUDE.md bagian 2.1 di sisi backend.
 */
beforeEach(function () {
    $this->superadmin = User::factory()->superadmin()->create();

    $this->perusahaanA = Perusahaan::factory()->create();
    $this->perusahaanB = Perusahaan::factory()->create();

    $this->guruA = User::factory()->guru('TKJ')->create();
    $this->guruB = User::factory()->guru('RPL')->create();
    $this->guruTanpaKelompok = User::factory()->guru('TKJ')->create();

    $this->industriA = User::factory()->industri($this->perusahaanA)->create();
    $this->industriB = User::factory()->industri($this->perusahaanB)->create();

    $this->kelompokA = KelompokMagang::factory()->create([
        'perusahaan_id' => $this->perusahaanA->id,
        'guru_pembimbing_id' => $this->guruA->id,
    ]);
    $this->kelompokB = KelompokMagang::factory()->create([
        'perusahaan_id' => $this->perusahaanB->id,
        'guru_pembimbing_id' => $this->guruB->id,
    ]);

    $this->siswaA = User::factory()->siswa($this->kelompokA, 'TKJ')->create();
    $this->siswaA2 = User::factory()->siswa($this->kelompokA, 'TKJ')->create();
    $this->siswaB = User::factory()->siswa($this->kelompokB, 'RPL')->create();
});

test('cakupan daftar siswa sesuai role', function () {
    $lihat = fn (User $viewer) => User::query()->siswaDalamCakupan($viewer)->pluck('id')->sort()->values()->all();

    expect($lihat($this->superadmin))->toBe(collect([$this->siswaA->id, $this->siswaA2->id, $this->siswaB->id])->sort()->values()->all())
        ->and($lihat($this->guruA))->toBe([$this->siswaA->id, $this->siswaA2->id])
        ->and($lihat($this->guruTanpaKelompok))->toBe([])
        ->and($lihat($this->industriB))->toBe([$this->siswaB->id])
        ->and($lihat($this->siswaA))->toBe([$this->siswaA->id]);
});

test('guru yang membimbing lebih dari satu kelompok melihat semua kelompoknya', function () {
    $kelompokLain = KelompokMagang::factory()->create(['guru_pembimbing_id' => $this->guruA->id]);
    $siswaLain = User::factory()->siswa($kelompokLain)->create();

    expect($siswaLain->dapatDilihatOleh($this->guruA))->toBeTrue()
        ->and($siswaLain->dapatDilihatOleh($this->guruB))->toBeFalse();
});

test('hanya superadmin yang mengelola akun, perusahaan, dan kelompok', function () {
    foreach ([$this->guruA, $this->industriA, $this->siswaA] as $bukanAdmin) {
        expect($bukanAdmin->can('create', User::class))->toBeFalse()
            ->and($bukanAdmin->can('update', $this->siswaB))->toBeFalse()
            ->and($bukanAdmin->can('create', Perusahaan::class))->toBeFalse()
            ->and($bukanAdmin->can('update', $this->perusahaanA))->toBeFalse()
            ->and($bukanAdmin->can('create', KelompokMagang::class))->toBeFalse();
    }

    expect($this->superadmin->can('create', User::class))->toBeTrue()
        ->and($this->superadmin->can('update', $this->guruA))->toBeTrue()
        ->and($this->superadmin->can('create', Perusahaan::class))->toBeTrue()
        ->and($this->superadmin->can('update', $this->kelompokA))->toBeTrue();
});

test('guru hanya melihat kelompok yang ia bimbing', function () {
    expect($this->guruA->can('view', $this->kelompokA))->toBeTrue()
        ->and($this->guruA->can('view', $this->kelompokB))->toBeFalse();
});

test('verifikasi kompetensi hanya oleh industri perusahaan siswa dan saat menunggu verifikasi', function () {
    $progres = ProgresKompetensi::create([
        'siswa_id' => $this->siswaA->id,
        'kompetensi_id' => Kompetensi::factory()->create(['dibuat_oleh' => $this->guruA->id])->id,
        'status' => StatusKompetensi::SedangDipraktikkan,
    ]);

    expect($this->industriA->can('verifikasi', $progres))->toBeFalse();

    $progres->update(['status' => StatusKompetensi::MenungguVerifikasi]);

    expect($this->industriA->can('verifikasi', $progres))->toBeTrue()
        ->and($this->industriB->can('verifikasi', $progres))->toBeFalse()
        ->and($this->guruA->can('verifikasi', $progres))->toBeFalse();

    $progres->update(['status' => StatusKompetensi::Terverifikasi]);

    expect($this->industriA->can('verifikasi', $progres))->toBeFalse();
});

test('kompetensi dan materi hanya diedit guru dengan program keahlian yang sama', function () {
    $kompetensiTkj = Kompetensi::factory()->create(['program_keahlian' => 'TKJ', 'dibuat_oleh' => $this->guruA->id]);
    $materiTkj = Materi::factory()->create(['kompetensi_id' => $kompetensiTkj->id, 'dibuat_oleh' => $this->guruA->id]);

    expect($this->guruA->can('update', $kompetensiTkj))->toBeTrue()
        ->and($this->guruTanpaKelompok->can('update', $materiTkj))->toBeTrue()
        ->and($this->guruB->can('update', $kompetensiTkj))->toBeFalse()
        ->and($this->guruB->can('update', $materiTkj))->toBeFalse()
        ->and($this->industriA->can('update', $materiTkj))->toBeFalse()
        ->and($this->superadmin->can('update', $materiTkj))->toBeFalse();
});

test('guru tanpa program keahlian tidak bisa membuat kompetensi atau materi', function () {
    $guru = User::factory()->guru(null)->create();

    expect($guru->can('create', Kompetensi::class))->toBeFalse()
        ->and($guru->can('create', Materi::class))->toBeFalse();
});

test('materi dilihat sesuai program keahlian, diverifikasi industri yang relevan', function () {
    $kompetensiTkj = Kompetensi::factory()->create(['program_keahlian' => 'TKJ', 'dibuat_oleh' => $this->guruA->id]);
    $materiTkj = Materi::factory()->create(['kompetensi_id' => $kompetensiTkj->id, 'dibuat_oleh' => $this->guruA->id]);

    expect($this->superadmin->can('view', $materiTkj))->toBeTrue()
        ->and($this->guruB->can('view', $materiTkj))->toBeTrue()
        ->and($this->siswaA->can('view', $materiTkj))->toBeTrue()
        ->and($this->siswaB->can('view', $materiTkj))->toBeFalse()
        ->and($this->industriA->can('view', $materiTkj))->toBeTrue()
        ->and($this->industriB->can('view', $materiTkj))->toBeFalse()
        ->and($this->industriA->can('verifikasi', $materiTkj))->toBeTrue()
        ->and($this->industriB->can('verifikasi', $materiTkj))->toBeFalse()
        ->and($this->guruA->can('verifikasi', $materiTkj))->toBeFalse();
});

test('logbook dan hasil assessment hanya dilihat pihak yang berhak', function () {
    $logbook = Logbook::create([
        'siswa_id' => $this->siswaA->id,
        'tanggal' => now()->toDateString(),
        'aktivitas' => 'Memasang kabel jaringan',
    ]);
    $hasil = HasilAssessment::create([
        'siswa_id' => $this->siswaA->id,
        'kuis_id' => Kuis::create(['materi_id' => Materi::factory()->create()->id])->id,
        'skor' => 80,
        'lulus' => true,
        'tanggal' => now(),
    ]);

    foreach ([$logbook, $hasil] as $data) {
        expect($this->superadmin->can('view', $data))->toBeTrue()
            ->and($this->guruA->can('view', $data))->toBeTrue()
            ->and($this->industriA->can('view', $data))->toBeTrue()
            ->and($this->siswaA->can('view', $data))->toBeTrue()
            ->and($this->guruB->can('view', $data))->toBeFalse()
            ->and($this->industriB->can('view', $data))->toBeFalse()
            ->and($this->siswaA2->can('view', $data))->toBeFalse()
            ->and($this->siswaB->can('view', $data))->toBeFalse();
    }

    expect($this->siswaA->can('update', $logbook))->toBeTrue()
        ->and($this->siswaA2->can('update', $logbook))->toBeFalse()
        ->and($this->guruA->can('update', $logbook))->toBeFalse();
});

test('dokumen sekolah oleh guru, dokumen industri oleh superadmin', function () {
    expect($this->guruA->can('create', [DokumenKnowledgeBase::class, JenisDokumen::Sekolah]))->toBeTrue()
        ->and($this->guruA->can('create', [DokumenKnowledgeBase::class, JenisDokumen::Industri]))->toBeFalse()
        ->and($this->superadmin->can('create', [DokumenKnowledgeBase::class, JenisDokumen::Industri]))->toBeTrue()
        ->and($this->superadmin->can('create', [DokumenKnowledgeBase::class, JenisDokumen::Sekolah]))->toBeFalse()
        ->and($this->industriA->can('create', [DokumenKnowledgeBase::class, JenisDokumen::Industri]))->toBeFalse();

    $dokumenTkj = DokumenKnowledgeBase::create([
        'jenis' => JenisDokumen::Sekolah,
        'program_keahlian' => 'TKJ',
        'nama_file' => 'modul.pdf',
        'path_file' => 'knowledge-base/modul.pdf',
        'diunggah_oleh' => $this->guruA->id,
    ]);

    expect($this->guruA->can('delete', $dokumenTkj))->toBeTrue()
        ->and($this->guruB->can('delete', $dokumenTkj))->toBeFalse();
});

test('AI Mentor hanya untuk siswa', function () {
    expect(Gate::forUser($this->siswaA)->allows('gunakan-ai-mentor'))->toBeTrue();

    foreach ([$this->superadmin, $this->guruA, $this->industriA] as $user) {
        expect(Gate::forUser($user)->allows('gunakan-ai-mentor'))->toBeFalse();
    }
});
