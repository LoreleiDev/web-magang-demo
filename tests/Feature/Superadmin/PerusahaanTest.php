<?php

use App\Enums\JenisDokumen;
use App\Models\DokumenKnowledgeBase;
use App\Models\KelompokMagang;
use App\Models\Perusahaan;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    $this->admin = User::factory()->superadmin()->create();
});

test('halaman perusahaan hanya untuk superadmin', function (string $role) {
    $user = User::factory()->{$role}()->create();
    $perusahaan = Perusahaan::factory()->create();

    $this->actingAs($user)->get(route('superadmin.perusahaan.index'))->assertForbidden();
    $this->actingAs($user)->delete(route('superadmin.perusahaan.destroy', $perusahaan))->assertForbidden();
    $this->actingAs($user)->post(route('superadmin.perusahaan.dokumen.store', $perusahaan), [])->assertForbidden();
})->with(['guru', 'industri', 'siswa']);

test('superadmin menambah perusahaan sekaligus mengunggah dokumen industri', function () {
    $this->actingAs($this->admin)->post(route('superadmin.perusahaan.store'), [
        'nama' => 'PT Maju Jaya',
        'alamat' => 'Bandung',
        'bidang_usaha' => 'ISP',
        'daftar_unit_kerja' => [' NOC ', 'Helpdesk'],
        'dokumen' => [
            UploadedFile::fake()->create('sop-noc.pdf', 200, 'application/pdf'),
            UploadedFile::fake()->create('panduan.docx', 100),
        ],
    ])->assertRedirect(route('superadmin.perusahaan.index'));

    $perusahaan = Perusahaan::where('nama', 'PT Maju Jaya')->firstOrFail();

    expect($perusahaan->daftar_unit_kerja)->toBe(['NOC', 'Helpdesk'])
        ->and($perusahaan->dokumen)->toHaveCount(2);

    $dokumen = $perusahaan->dokumen->firstWhere('nama_file', 'sop-noc.pdf');

    expect($dokumen->jenis)->toBe(JenisDokumen::Industri)
        ->and($dokumen->diunggah_oleh)->toBe($this->admin->id)
        ->and($dokumen->id_di_layanan_ai)->toBeNull();

    Storage::disk('local')->assertExists($dokumen->path_file);
});

test('validasi perusahaan: unit kerja wajib & tidak boleh kembar, jenis dokumen dibatasi', function () {
    $this->actingAs($this->admin)->post(route('superadmin.perusahaan.store'), [
        'nama' => '',
        'daftar_unit_kerja' => [],
    ])->assertSessionHasErrors(['nama', 'daftar_unit_kerja']);

    $this->actingAs($this->admin)->post(route('superadmin.perusahaan.store'), [
        'nama' => 'PT A',
        'daftar_unit_kerja' => ['NOC', 'noc'],
        'dokumen' => [UploadedFile::fake()->create('virus.exe', 10)],
    ])->assertSessionHasErrors(['daftar_unit_kerja.1', 'dokumen.0']);
});

test('unit kerja yang masih dipilih siswa tidak bisa dihapus', function () {
    $perusahaan = Perusahaan::factory()->create(['daftar_unit_kerja' => ['NOC', 'Helpdesk']]);
    $kelompok = KelompokMagang::factory()->create(['perusahaan_id' => $perusahaan->id]);
    User::factory()->siswa($kelompok, 'TKJ', 'NOC')->create();

    $this->actingAs($this->admin)->put(route('superadmin.perusahaan.update', $perusahaan), [
        'nama' => $perusahaan->nama,
        'daftar_unit_kerja' => ['Helpdesk'],
    ])->assertSessionHasErrors('daftar_unit_kerja');

    $this->actingAs($this->admin)->put(route('superadmin.perusahaan.update', $perusahaan), [
        'nama' => $perusahaan->nama,
        'daftar_unit_kerja' => ['NOC', 'Instalasi'],
    ])->assertSessionHasNoErrors();

    expect($perusahaan->fresh()->daftar_unit_kerja)->toBe(['NOC', 'Instalasi']);
});

test('perusahaan yang dipakai kelompok tidak bisa dihapus, yang kosong bisa beserta dokumennya', function () {
    $dipakai = Perusahaan::factory()->create();
    KelompokMagang::factory()->create(['perusahaan_id' => $dipakai->id]);

    $this->actingAs($this->admin)->delete(route('superadmin.perusahaan.destroy', $dipakai));
    expect($dipakai->fresh())->not->toBeNull();

    $kosong = Perusahaan::factory()->create();
    $this->actingAs($this->admin)->post(route('superadmin.perusahaan.dokumen.store', $kosong), [
        'dokumen' => [UploadedFile::fake()->create('sop.pdf', 50, 'application/pdf')],
    ]);
    $path = $kosong->dokumen()->first()->path_file;

    $this->actingAs($this->admin)->delete(route('superadmin.perusahaan.destroy', $kosong))
        ->assertRedirect(route('superadmin.perusahaan.index'));

    expect($kosong->fresh())->toBeNull()
        ->and(DokumenKnowledgeBase::count())->toBe(0);
    Storage::disk('local')->assertMissing($path);
});

test('dokumen industri bisa ditambah, diunduh, dan dihapus lewat edit perusahaan', function () {
    $perusahaan = Perusahaan::factory()->create();

    $this->actingAs($this->admin)->post(route('superadmin.perusahaan.dokumen.store', $perusahaan), [
        'dokumen' => [UploadedFile::fake()->create('sop.pdf', 50, 'application/pdf')],
    ])->assertSessionHasNoErrors();

    $dokumen = $perusahaan->dokumen()->firstOrFail();

    $this->actingAs($this->admin)
        ->get(route('superadmin.perusahaan.dokumen.show', [$perusahaan, $dokumen]))
        ->assertOk()
        ->assertDownload('sop.pdf');

    $this->actingAs($this->admin)
        ->delete(route('superadmin.perusahaan.dokumen.destroy', [$perusahaan, $dokumen]))
        ->assertRedirect();

    expect(DokumenKnowledgeBase::count())->toBe(0);
    Storage::disk('local')->assertMissing($dokumen->path_file);
});

test('dokumen perusahaan lain tidak bisa diakses lewat URL perusahaan ini', function () {
    $a = Perusahaan::factory()->create();
    $b = Perusahaan::factory()->create();

    $this->actingAs($this->admin)->post(route('superadmin.perusahaan.dokumen.store', $b), [
        'dokumen' => [UploadedFile::fake()->create('rahasia-b.pdf', 50, 'application/pdf')],
    ]);
    $dokumenB = $b->dokumen()->firstOrFail();

    $this->actingAs($this->admin)->get(route('superadmin.perusahaan.dokumen.show', [$a, $dokumenB]))->assertNotFound();
    $this->actingAs($this->admin)->delete(route('superadmin.perusahaan.dokumen.destroy', [$a, $dokumenB]))->assertNotFound();

    expect($dokumenB->fresh())->not->toBeNull();
});
