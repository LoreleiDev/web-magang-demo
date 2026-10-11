<?php

use App\Models\KelompokMagang;
use App\Models\Kompetensi;
use App\Models\Logbook;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/*
| File upload tidak boleh bisa dipakai menjalankan skrip (XSS tersimpan) di browser
| guru/industri/admin yang membukanya.
*/

beforeEach(function () {
    Storage::fake('local');
    $this->guru = User::factory()->guru('TKJ')->create();
    $kelompok = KelompokMagang::factory()->create(['guru_pembimbing_id' => $this->guru->id]);
    $this->siswa = siswaSiap(
        User::factory()->siswa($kelompok, 'TKJ', 'Jaringan')->create(),
        Kompetensi::factory()->create(['program_keahlian' => 'TKJ'])->id,
    );
});

test('file HTML yang menyamar sebagai gambar ditolak', function () {
    // File sungguhan (bukan UploadedFile::fake, yang menebak MIME dari nama file).
    $path = tempnam(sys_get_temp_dir(), 'xss');
    file_put_contents($path, '<!DOCTYPE html><html><body><script>alert(document.cookie)</script></body></html>');
    $palsu = new UploadedFile($path, 'foto.png', null, null, true);

    $this->actingAs($this->siswa)
        ->post(route('siswa.logbook.store'), [
            'tanggal' => now()->toDateString(),
            'aktivitas' => 'Uji',
            'bukti_kegiatan' => $palsu,
        ])
        ->assertSessionHasErrors('bukti_kegiatan');

    expect(Logbook::count())->toBe(0);
});

test('bukti kegiatan disajikan dengan header aman', function () {
    $this->actingAs($this->siswa)->post(route('siswa.logbook.store'), [
        'tanggal' => now()->toDateString(),
        'aktivitas' => 'Uji',
        'bukti_kegiatan' => UploadedFile::fake()->image('foto.png'),
    ]);

    $respons = $this->actingAs($this->guru)->get(route('logbook.bukti', Logbook::sole()));

    $respons->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Content-Type', 'image/png');
    expect($respons->headers->get('Content-Security-Policy'))->toContain('sandbox');
});

test('rute bawaan storage untuk file privat tidak tersedia', function () {
    Storage::disk('local')->put('logbook/1/rahasia.png', 'x');

    $this->get('/storage/logbook/1/rahasia.png')->assertNotFound();
    $this->put('/storage/logbook/1/baru.png', ['x' => 1])->assertNotFound();
});

test('semua halaman mengirim header keamanan', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
});

test('IP klien dari header X-Forwarded-For hanya dipercaya dari proxy lokal', function () {
    // Permintaan langsung (bukan dari 127.0.0.1) tidak bisa memalsukan IP / https.
    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.9'])
        ->withHeaders(['X-Forwarded-For' => '1.2.3.4', 'X-Forwarded-Proto' => 'https', 'X-Forwarded-Host' => 'jahat.test'])
        ->get('/login/guru')
        ->assertOk();
    expect(request()->ip())->toBe('203.0.113.9');

    // Lewat proxy lokal (ngrok), https & host dari proxy dipakai.
    $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
        ->withHeaders(['X-Forwarded-Proto' => 'https', 'X-Forwarded-Host' => 'demo.ngrok-free.app', 'X-Forwarded-Port' => '443'])
        ->get('/guru')
        ->assertRedirect('https://demo.ngrok-free.app/login/guru');
});

test('halaman error memakai tampilan berbahasa Indonesia dengan status yang benar', function () {
    $this->get('/halaman-yang-tidak-ada')
        ->assertNotFound()
        ->assertInertia(fn ($page) => $page->component('error')->where('status', 404));

    $siswa = User::factory()->siswa()->create();
    $this->actingAs($siswa)->get('/superadmin')
        ->assertForbidden()
        ->assertInertia(fn ($page) => $page->component('error')->where('status', 403));

    // Permintaan JSON (AI Mentor) tetap menerima JSON.
    $this->actingAs(User::factory()->guru()->create())->getJson(route('siswa.mentor.riwayat'))
        ->assertForbidden()
        ->assertJsonStructure(['message']);
});
