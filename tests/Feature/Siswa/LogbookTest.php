<?php

use App\Models\KelompokMagang;
use App\Models\Kompetensi;
use App\Models\Logbook;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake('local');
    $this->guru = User::factory()->guru('TKJ')->create();
    $this->kelompok = KelompokMagang::factory()->create(['guru_pembimbing_id' => $this->guru->id]);
    $this->siswa = User::factory()->siswa($this->kelompok, 'TKJ', 'Jaringan')->create();
    $kompetensi = Kompetensi::factory()->create(['program_keahlian' => 'TKJ']);

    siswaSiap($this->siswa, $kompetensi->id);
    $this->masuk = fn (?User $s = null) => $this->actingAs($s === null ? $this->siswa : siswaSiap($s, $kompetensi->id));
});

function isiLogbook(array $timpa = []): array
{
    return [
        'tanggal' => now()->toDateString(),
        'aktivitas' => 'Memasang kabel UTP',
        'kesulitan' => 'Urutan warna T568B',
        ...$timpa,
    ];
}

test('siswa mengisi logbook beserta bukti kegiatan', function () {
    ($this->masuk)()
        ->post(route('siswa.logbook.store'), isiLogbook([
            'bukti_kegiatan' => UploadedFile::fake()->image('foto.jpg'),
        ]))
        ->assertRedirect(route('siswa.logbook.index'));

    $logbook = Logbook::firstOrFail();
    expect($logbook->siswa_id)->toBe($this->siswa->id)
        ->and($logbook->hasil_analisis_ai)->toBeNull();
    Storage::disk('local')->assertExists($logbook->bukti_kegiatan);

    ($this->masuk)()->get(route('siswa.logbook.index'))
        ->assertInertia(fn (Assert $page) => $page->has('logbook', 1)->whereNot('logbook.0.url_bukti', null));
});

test('satu logbook per tanggal, tanggal tidak boleh di masa depan, bukti dibatasi', function () {
    ($this->masuk)()->post(route('siswa.logbook.store'), isiLogbook());

    ($this->masuk)()
        ->post(route('siswa.logbook.store'), isiLogbook())
        ->assertSessionHasErrors('tanggal');

    ($this->masuk)()
        ->post(route('siswa.logbook.store'), isiLogbook(['tanggal' => now()->addDay()->toDateString()]))
        ->assertSessionHasErrors('tanggal');

    ($this->masuk)()
        ->post(route('siswa.logbook.store'), isiLogbook([
            'tanggal' => now()->subDay()->toDateString(),
            'aktivitas' => '',
            'bukti_kegiatan' => UploadedFile::fake()->create('file.exe', 10),
        ]))
        ->assertSessionHasErrors(['aktivitas', 'bukti_kegiatan']);

    ($this->masuk)()
        ->post(route('siswa.logbook.store'), isiLogbook([
            'tanggal' => now()->subDay()->toDateString(),
            'bukti_kegiatan' => UploadedFile::fake()->create('besar.pdf', 6000, 'application/pdf'),
        ]))
        ->assertSessionHasErrors('bukti_kegiatan');

    expect(Logbook::count())->toBe(1);
});

test('siswa mengedit logbook miliknya, tidak bisa milik siswa lain', function () {
    ($this->masuk)()->post(route('siswa.logbook.store'), isiLogbook());
    $logbook = Logbook::firstOrFail();

    ($this->masuk)()
        ->put(route('siswa.logbook.update', $logbook), isiLogbook(['aktivitas' => 'Konfigurasi VLAN']))
        ->assertSessionHasNoErrors();
    expect($logbook->fresh()->aktivitas)->toBe('Konfigurasi VLAN');

    $siswaLain = User::factory()->siswa($this->kelompok, 'TKJ', 'Jaringan')->create();
    ($this->masuk)($siswaLain)
        ->put(route('siswa.logbook.update', $logbook), isiLogbook(['aktivitas' => 'Diubah orang lain']))
        ->assertForbidden();

    expect($logbook->fresh()->aktivitas)->toBe('Konfigurasi VLAN');
});

test('bukti kegiatan hanya bisa dibuka pihak yang berhak', function () {
    ($this->masuk)()->post(route('siswa.logbook.store'), isiLogbook([
        'bukti_kegiatan' => UploadedFile::fake()->create('bukti.pdf', 50, 'application/pdf'),
    ]));
    $logbook = Logbook::firstOrFail();

    $this->actingAs($this->siswa)->get(route('logbook.bukti', $logbook))->assertOk();
    $this->actingAs($this->guru)->get(route('logbook.bukti', $logbook))->assertOk();
    $this->actingAs(User::factory()->superadmin()->create())->get(route('logbook.bukti', $logbook))->assertOk();

    $this->actingAs(User::factory()->guru('TKJ')->create())->get(route('logbook.bukti', $logbook))->assertForbidden();
    $this->actingAs(User::factory()->industri()->create())->get(route('logbook.bukti', $logbook))->assertForbidden();
    $this->actingAs(User::factory()->siswa($this->kelompok)->create())->get(route('logbook.bukti', $logbook))->assertForbidden();
});

test('dashboard tidak lagi meminta logbook setelah diisi hari ini', function () {
    ($this->masuk)()->post(route('siswa.logbook.store'), isiLogbook());

    ($this->masuk)()->get(route('siswa.dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('aktivitasHariIni', fn ($a) => collect($a)->doesntContain('jenis', 'logbook'))
            ->where('logbookTerakhir.aktivitas', 'Memasang kabel UTP'));
});
