<?php

use App\Jobs\KirimEmailMasukanMateri;
use App\Mail\MasukanMateriMail;
use App\Models\KelompokMagang;
use App\Models\Kompetensi;
use App\Models\Materi;
use App\Models\Perusahaan;
use App\Models\User;
use App\Models\VerifikasiMateri;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * Industri di perusahaan baru yang punya siswa TKJ, sehingga boleh memeriksa materi TKJ.
 */
function industriTkj(): User
{
    $perusahaan = Perusahaan::factory()->create();
    User::factory()->siswa(KelompokMagang::factory()->create(['perusahaan_id' => $perusahaan->id]), 'TKJ')->create();

    return User::factory()->industri($perusahaan)->create();
}

beforeEach(function () {
    $this->guru = User::factory()->guru('TKJ')->create(['email' => 'guru@sekolah.test']);
    $this->materi = Materi::factory()->create([
        'kompetensi_id' => Kompetensi::factory()->create(['program_keahlian' => 'TKJ'])->id,
        'dibuat_oleh' => $this->guru->id,
        'diubah_terakhir' => now()->subDay(),
    ]);
    $this->industriA = industriTkj();
    $this->industriB = industriTkj();
});

test('industri hanya melihat materi program keahlian siswa di perusahaannya', function () {
    Materi::factory()->create(['kompetensi_id' => Kompetensi::factory()->create(['program_keahlian' => 'RPL'])->id]);
    $materiRpl = Materi::latest('id')->first();

    $this->actingAs($this->industriA)
        ->get(route('industri.materi.index'))
        ->assertInertia(fn (Assert $page) => $page->has('materi', 1)->where('materi.0.id', $this->materi->id));

    $this->actingAs($this->industriA)->get(route('industri.materi.show', $materiRpl))->assertForbidden();
    $this->actingAs($this->industriA)
        ->post(route('industri.materi.verifikasi', $materiRpl), ['hasil' => 'diverifikasi'])
        ->assertForbidden();
});

test('verifikasi menyimpan hasil, memunculkan badge, dan mengirim email lewat queue', function () {
    Queue::fake();

    $this->actingAs($this->industriA)
        ->post(route('industri.materi.verifikasi', $this->materi), ['hasil' => 'diverifikasi'])
        ->assertRedirect(route('industri.materi.index'));

    $v = VerifikasiMateri::firstOrFail();
    expect($v->diperiksa_oleh)->toBe($this->industriA->id)
        ->and($v->perusahaan_id)->toBe($this->industriA->perusahaanId())
        ->and($v->email_terkirim)->toBeFalse()
        ->and($this->materi->fresh()->terverifikasi_industri)->toBeTrue();

    Queue::assertPushed(KirimEmailMasukanMateri::class, fn ($job) => $job->verifikasi->is($v));
});

test('"Belum Sesuai" wajib disertai masukan', function () {
    $this->actingAs($this->industriA)
        ->post(route('industri.materi.verifikasi', $this->materi), ['hasil' => 'belum_sesuai', 'masukan' => ''])
        ->assertSessionHasErrors('masukan');

    $this->actingAs($this->industriA)
        ->post(route('industri.materi.verifikasi', $this->materi), ['hasil' => 'lainnya'])
        ->assertSessionHasErrors('hasil');

    expect(VerifikasiMateri::count())->toBe(0);
});

test('aturan badge: hasil terakhir tiap perusahaan, "Belum Sesuai" menahan badge', function () {
    Queue::fake();
    $periksa = fn (User $u, string $hasil) => $this->actingAs($u)->post(
        route('industri.materi.verifikasi', $this->materi),
        ['hasil' => $hasil, 'masukan' => 'Catatan'],
    );

    $periksa($this->industriA, 'diverifikasi');
    expect($this->materi->fresh()->terverifikasi_industri)->toBeTrue();

    $periksa($this->industriB, 'belum_sesuai');
    expect($this->materi->fresh()->terverifikasi_industri)->toBeFalse();

    // Perusahaan B memeriksa ulang dan memverifikasi -> badge kembali.
    $this->travel(1)->minutes();
    $periksa($this->industriB, 'diverifikasi');
    expect($this->materi->fresh()->terverifikasi_industri)->toBeTrue();
});

test('edit materi oleh guru membuat hasil pemeriksaan lama tidak dihitung lagi', function () {
    Queue::fake();
    $this->actingAs($this->industriA)
        ->post(route('industri.materi.verifikasi', $this->materi), ['hasil' => 'diverifikasi']);

    $this->travel(1)->minutes();
    $this->materi->update(['diubah_terakhir' => now(), 'terverifikasi_industri' => false]);

    expect(app(App\Services\VerifikasiMateriService::class)->badgeTampil($this->materi->fresh()))->toBeFalse();

    $this->actingAs($this->industriA)
        ->get(route('industri.materi.index'))
        ->assertInertia(fn (Assert $page) => $page->where('materi.0.pemeriksaan_saya.perlu_ulang', true));
});

test('email berisi subjek, hasil, masukan, dan link edit; email_terkirim menjadi true', function () {
    Mail::fake();

    $this->actingAs($this->industriA)->post(route('industri.materi.verifikasi', $this->materi), [
        'hasil' => 'belum_sesuai',
        'masukan' => 'Tambahkan contoh VLSM.',
    ]);

    Mail::assertSent(MasukanMateriMail::class, function (MasukanMateriMail $mail) {
        $mail->assertHasSubject("[MagangBridge] Masukan untuk materi \"{$this->materi->judul}\"");
        $mail->assertSeeInHtml('Belum Sesuai');
        $mail->assertSeeInHtml('Tambahkan contoh VLSM.');
        $mail->assertSeeInHtml($this->industriA->name);
        $mail->assertSeeInHtml(route('guru.materi.edit', $this->materi));

        return $mail->hasTo('guru@sekolah.test');
    });

    expect(VerifikasiMateri::firstOrFail()->email_terkirim)->toBeTrue();
});

test('email gagal tidak membatalkan verifikasi; email_terkirim tetap false', function () {
    $v = VerifikasiMateri::create([
        'materi_id' => $this->materi->id,
        'diperiksa_oleh' => $this->industriA->id,
        'perusahaan_id' => $this->industriA->perusahaanId(),
        'hasil' => 'belum_sesuai',
        'masukan' => 'Perbaiki',
        'email_terkirim' => false,
    ]);

    (new KirimEmailMasukanMateri($v))->failed(new RuntimeException('SMTP mati'));

    expect($v->fresh())->not->toBeNull()
        ->and($v->fresh()->email_terkirim)->toBeFalse();
});

test('guru membaca masukan di daftar materinya walau email tidak sampai', function () {
    Queue::fake();
    $this->actingAs($this->industriA)->post(route('industri.materi.verifikasi', $this->materi), [
        'hasil' => 'belum_sesuai',
        'masukan' => 'Tambahkan contoh VLSM.',
    ]);

    $this->actingAs($this->guru)
        ->get(route('guru.materi.index'))
        ->assertInertia(fn (Assert $page) => $page->where('materi.0.masukan_terakhir.masukan', 'Tambahkan contoh VLSM.'));
});

test('siswa melihat badge pada materi yang diverifikasi', function () {
    Queue::fake();
    $this->actingAs($this->industriA)->post(route('industri.materi.verifikasi', $this->materi), ['hasil' => 'diverifikasi']);

    $siswa = User::factory()->siswa(KelompokMagang::factory()->create(), 'TKJ', 'NOC')->create();

    $this->actingAs($siswa)
        ->withSession(['pendampingan.kompetensi_id' => $this->materi->kompetensi_id])
        ->get(route('siswa.belajar.index'))
        ->assertInertia(fn (Assert $page) => $page->where('kompetensi.0.materi.0.terverifikasi_industri', true));
});
