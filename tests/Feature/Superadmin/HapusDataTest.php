<?php

use App\Enums\StatusKompetensi;
use App\Jobs\KirimEmailMasukanMateri;
use App\Models\ChatAiMentor;
use App\Models\DokumenKnowledgeBase;
use App\Models\KelompokMagang;
use App\Models\Kompetensi;
use App\Models\Logbook;
use App\Models\Materi;
use App\Models\ProfilSiswa;
use App\Models\ProgresKompetensi;
use App\Models\User;
use App\Models\VerifikasiMateri;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

/*
| Hapus akun & kelompok oleh superadmin (revisi keputusan 13 no. 25–26).
*/

beforeEach(function () {
    Storage::fake('local');
    Queue::fake();

    $this->admin = User::factory()->superadmin()->create();
    $this->guru = User::factory()->guru('TKJ')->create();
    $this->kelompok = KelompokMagang::factory()->create(['guru_pembimbing_id' => $this->guru->id]);
    $this->siswa = User::factory()->siswa($this->kelompok, 'TKJ', 'Jaringan')->create();
    $this->industri = User::factory()->industri($this->kelompok->perusahaan)->create(['name' => 'Hendra']);
    $this->kompetensi = Kompetensi::factory()->create(['program_keahlian' => 'TKJ', 'dibuat_oleh' => $this->guru->id, 'target_level' => 3]);
    $this->materi = Materi::factory()->create(['kompetensi_id' => $this->kompetensi->id, 'dibuat_oleh' => $this->guru->id]);
});

test('hapus siswa menghapus semua datanya beserta file bukti kegiatan', function () {
    $bukti = UploadedFile::fake()->image('foto.jpg')->store('bukti', 'local');
    $logbook = Logbook::create(['siswa_id' => $this->siswa->id, 'tanggal' => now()->toDateString(), 'aktivitas' => 'X', 'bukti_kegiatan' => $bukti]);
    ProgresKompetensi::create(['siswa_id' => $this->siswa->id, 'kompetensi_id' => $this->kompetensi->id, 'level_siswa' => 2]);
    ChatAiMentor::create(['siswa_id' => $this->siswa->id, 'pesan' => [['peran' => 'siswa', 'isi' => 'Halo']]]);

    $this->actingAs($this->admin)
        ->delete(route('superadmin.akun.destroy', $this->siswa))
        ->assertRedirect(route('superadmin.akun.index'));

    expect(User::find($this->siswa->id))->toBeNull()
        ->and(ProfilSiswa::where('user_id', $this->siswa->id)->exists())->toBeFalse()
        ->and(Logbook::find($logbook->id))->toBeNull()
        ->and(ProgresKompetensi::where('siswa_id', $this->siswa->id)->exists())->toBeFalse()
        ->and(ChatAiMentor::where('siswa_id', $this->siswa->id)->exists())->toBeFalse();
    Storage::disk('local')->assertMissing($bukti);
});

test('hapus guru: kelompok, kompetensi, materi, dan dokumennya tetap ada', function () {
    $dokumen = DokumenKnowledgeBase::create([
        'jenis' => 'sekolah', 'program_keahlian' => 'TKJ', 'nama_file' => 'modul.pdf',
        'path_file' => 'kb/modul.pdf', 'diunggah_oleh' => $this->guru->id,
    ]);

    $this->actingAs($this->admin)->delete(route('superadmin.akun.destroy', $this->guru))->assertRedirect();

    expect(User::find($this->guru->id))->toBeNull()
        ->and($this->kelompok->fresh()->guru_pembimbing_id)->toBeNull()
        ->and($this->kompetensi->fresh()->dibuat_oleh)->toBeNull()
        ->and($this->materi->fresh()->dibuat_oleh)->toBeNull()
        ->and($dokumen->fresh()->diunggah_oleh)->toBeNull()
        ->and($this->siswa->profilSiswa->fresh()->kelompok_id)->toBe($this->kelompok->id);

    // Halaman tetap terbuka walau pemiliknya sudah tidak ada.
    $this->actingAs($this->admin)->get(route('superadmin.kelompok.index'))
        ->assertInertia(fn (Assert $page) => $page->where('kelompok.0.guru', null));
    $this->actingAs($this->admin)->get(route('superadmin.kelompok.edit', $this->kelompok))->assertOk();
    $this->actingAs($this->admin)->get(route('superadmin.materi.show', $this->materi))->assertOk();
    siswaSiap($this->siswa, $this->kompetensi->id);
    $this->actingAs($this->siswa)->get(route('siswa.belajar.show', $this->materi))->assertOk();

    // Guru lain dengan program sama tetap bisa mengelola kompetensi & materinya.
    $guruBaru = User::factory()->guru('TKJ')->create();
    $this->actingAs($guruBaru)->get(route('guru.kompetensi.index'))
        ->assertInertia(fn (Assert $page) => $page->where('kompetensi.0.pembuat', 'Guru (akun dihapus)'));
    $this->actingAs($guruBaru)->get(route('guru.materi.edit', $this->materi))->assertOk();
});

test('verifikasi materi untuk materi yang pembuatnya dihapus tetap tersimpan tanpa email', function () {
    $this->guru->delete();

    $this->actingAs($this->industri)
        ->post(route('industri.materi.verifikasi', $this->materi), ['hasil' => 'belum_sesuai', 'masukan' => 'Tambah contoh'])
        ->assertRedirect();

    $verifikasi = VerifikasiMateri::sole();
    Queue::assertPushed(KirimEmailMasukanMateri::class);
    (new KirimEmailMasukanMateri($verifikasi))->handle();

    expect($verifikasi->fresh()->email_terkirim)->toBeFalse()
        ->and($verifikasi->masukan)->toBe('Tambah contoh');
});

test('hapus industri: riwayat verifikasi tetap ada dengan nama pemeriksa', function () {
    $progres = ProgresKompetensi::create([
        'siswa_id' => $this->siswa->id, 'kompetensi_id' => $this->kompetensi->id,
        'level_siswa' => 3, 'status' => StatusKompetensi::MenungguVerifikasi,
    ]);
    $this->actingAs($this->industri)->post(route('industri.siswa.verifikasi', [$this->siswa, $this->kompetensi]));
    $this->actingAs($this->industri)->post(route('industri.materi.verifikasi', $this->materi), ['hasil' => 'diverifikasi']);

    $this->actingAs($this->admin)->delete(route('superadmin.akun.destroy', $this->industri))->assertRedirect();

    expect(User::find($this->industri->id))->toBeNull()
        ->and($progres->fresh())
        ->status->toBe(StatusKompetensi::Terverifikasi)
        ->diverifikasi_oleh->toBeNull()
        ->nama_verifikator->toBe('Hendra')
        ->and(VerifikasiMateri::sole())
        ->diperiksa_oleh->toBeNull()
        ->nama_pemeriksa->toBe('Hendra');

    $this->actingAs($this->guru)->get(route('guru.siswa.show', $this->siswa))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('kompetensi.0.diverifikasi_oleh', 'Hendra'));
    $this->actingAs($this->guru)->get(route('guru.materi.index'))
        ->assertInertia(fn (Assert $page) => $page->where('materi.0.masukan_terakhir.pemeriksa', 'Hendra'));
});

test('hapus kelompok: siswa menjadi tanpa kelompok dan unit kerja dikosongkan, datanya tetap', function () {
    $logbook = Logbook::create(['siswa_id' => $this->siswa->id, 'tanggal' => now()->toDateString(), 'aktivitas' => 'X']);

    $this->actingAs($this->admin)
        ->delete(route('superadmin.kelompok.destroy', $this->kelompok))
        ->assertRedirect(route('superadmin.kelompok.index'));

    $profil = $this->siswa->profilSiswa->fresh();
    expect(KelompokMagang::find($this->kelompok->id))->toBeNull()
        ->and($profil->kelompok_id)->toBeNull()
        ->and($profil->unit_kerja)->toBeNull()
        ->and(Logbook::find($logbook->id))->not->toBeNull();

    $this->actingAs($this->siswa)->get(route('siswa.mulai'))->assertOk();
});

test('akun superadmin tidak bisa dihapus, dan hanya superadmin yang bisa menghapus', function () {
    $this->actingAs($this->admin)->delete(route('superadmin.akun.destroy', $this->admin))->assertForbidden();
    $this->actingAs($this->admin)->delete(route('superadmin.akun.destroy', User::factory()->superadmin()->create()))->assertForbidden();

    foreach ([$this->guru, $this->industri, $this->siswa] as $user) {
        $this->actingAs($user)->delete(route('superadmin.akun.destroy', $this->siswa))->assertForbidden();
        $this->actingAs($user)->delete(route('superadmin.kelompok.destroy', $this->kelompok))->assertForbidden();
    }

    expect(User::find($this->siswa->id))->not->toBeNull()
        ->and(KelompokMagang::find($this->kelompok->id))->not->toBeNull();
});
