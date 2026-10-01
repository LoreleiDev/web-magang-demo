<?php

use App\Enums\JenisDokumen;
use App\Models\DokumenKnowledgeBase;
use App\Models\Perusahaan;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake('local');
    $this->guru = User::factory()->guru('TKJ')->create();
});

test('guru mengunggah dokumen sekolah untuk program keahliannya', function () {
    $this->actingAs($this->guru)->post(route('guru.dokumen.store'), [
        'dokumen' => [UploadedFile::fake()->create('modul-subnetting.pdf', 100, 'application/pdf')],
    ])->assertSessionHasNoErrors();

    $dokumen = DokumenKnowledgeBase::firstOrFail();

    expect($dokumen->jenis)->toBe(JenisDokumen::Sekolah)
        ->and($dokumen->program_keahlian)->toBe('TKJ')
        ->and($dokumen->perusahaan_id)->toBeNull();
    Storage::disk('local')->assertExists($dokumen->path_file);

    $this->actingAs($this->guru)
        ->get(route('guru.dokumen.index'))
        ->assertInertia(fn (Assert $page) => $page->has('dokumen', 1));
});

test('guru tidak bisa menghapus/mengunduh dokumen program lain atau dokumen industri', function () {
    $guruRpl = User::factory()->guru('RPL')->create();
    $this->actingAs($guruRpl)->post(route('guru.dokumen.store'), [
        'dokumen' => [UploadedFile::fake()->create('rpl.pdf', 10, 'application/pdf')],
    ]);
    $dokumenRpl = DokumenKnowledgeBase::firstOrFail();

    $industri = DokumenKnowledgeBase::create([
        'jenis' => JenisDokumen::Industri,
        'perusahaan_id' => Perusahaan::factory()->create()->id,
        'nama_file' => 'sop.pdf',
        'path_file' => 'x/sop.pdf',
        'diunggah_oleh' => User::factory()->superadmin()->create()->id,
    ]);

    foreach ([$dokumenRpl, $industri] as $d) {
        $this->actingAs($this->guru)->get(route('guru.dokumen.show', $d))->assertForbidden();
        $this->actingAs($this->guru)->delete(route('guru.dokumen.destroy', $d))->assertForbidden();
    }

    expect(DokumenKnowledgeBase::count())->toBe(2);
});

test('guru menghapus dokumen sekolah miliknya', function () {
    $this->actingAs($this->guru)->post(route('guru.dokumen.store'), [
        'dokumen' => [UploadedFile::fake()->create('modul.pdf', 10, 'application/pdf')],
    ]);
    $dokumen = DokumenKnowledgeBase::firstOrFail();

    $this->actingAs($this->guru)->delete(route('guru.dokumen.destroy', $dokumen))->assertRedirect();

    expect(DokumenKnowledgeBase::count())->toBe(0);
    Storage::disk('local')->assertMissing($dokumen->path_file);
});

test('guru tanpa program keahlian tidak bisa mengunggah', function () {
    $guru = User::factory()->guru(null)->create();

    $this->actingAs($guru)->post(route('guru.dokumen.store'), [
        'dokumen' => [UploadedFile::fake()->create('modul.pdf', 10, 'application/pdf')],
    ])->assertForbidden();
});
