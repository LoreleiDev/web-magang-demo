<?php

use App\Jobs\HapusDokumenAi;
use App\Jobs\SinkronDokumenKnowledgeBase;
use App\Models\DokumenKnowledgeBase;
use App\Models\KnowledgeBaseStore;
use App\Models\Perusahaan;
use App\Models\User;
use App\Services\AiMentorService;
use App\Services\DokumenKnowledgeBaseService;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    $this->guru = User::factory()->guru('TKJ')->create();
    $this->admin = User::factory()->superadmin()->create();

    // Alur Gemini File Search: buat store -> unggah resumable -> importFile -> hapus file sementara.
    $this->fakeGemini = function () {
        $nomor = 0;
        Http::fake([
            '*/v1beta/fileSearchStores' => function () use (&$nomor) {
                $nomor++;

                return Http::response(['name' => "fileSearchStores/store-{$nomor}"]);
            },
            '*/upload/v1beta/files' => Http::response([], 200, ['X-Goog-Upload-URL' => 'https://unggah.test/sesi']),
            'unggah.test/*' => Http::response(['file' => ['name' => 'files/sementara']]),
            '*:importFile' => Http::response(['name' => 'operasi/1', 'done' => true, 'response' => ['documentName' => 'dok-1']]),
            '*/v1beta/files/sementara' => Http::response([]),
        ]);
    };
});

test('unggah dokumen sekolah & industri mengantrekan sinkron ke AI', function () {
    $this->actingAs($this->guru)->post(route('guru.dokumen.store'), [
        'dokumen' => [UploadedFile::fake()->create('modul.pdf', 10, 'application/pdf')],
    ]);
    $this->actingAs($this->admin)->post(route('superadmin.perusahaan.store'), [
        'nama' => 'PT Uji',
        'daftar_unit_kerja' => ['Jaringan'],
        'dokumen' => [UploadedFile::fake()->create('sop.pdf', 10, 'application/pdf')],
    ])->assertSessionHasNoErrors();

    expect(DokumenKnowledgeBase::pluck('status_ai')->all())->toBe(['menunggu', 'menunggu']);
    Queue::assertPushed(SinkronDokumenKnowledgeBase::class, 2);
});

test('sinkron membuat satu store per cakupan dan menandai dokumen siap', function () {
    ($this->fakeGemini)();
    $perusahaan = Perusahaan::factory()->create();
    $service = app(DokumenKnowledgeBaseService::class);

    $modul1 = $service->simpanSekolah(UploadedFile::fake()->create('modul-1.pdf', 10), 'TKJ', $this->guru);
    $modul2 = $service->simpanSekolah(UploadedFile::fake()->create('modul-2.txt', 10), 'TKJ', $this->guru);
    $sop = $service->simpanIndustri(UploadedFile::fake()->create('sop.pdf', 10), $perusahaan->id, $this->admin);

    foreach ([$modul1, $modul2, $sop] as $d) {
        (new SinkronDokumenKnowledgeBase($d))->handle(app(AiMentorService::class));
    }

    expect(KnowledgeBaseStore::pluck('nama_store', 'cakupan')->all())->toBe([
        'sekolah:TKJ' => 'fileSearchStores/store-1',
        "industri:{$perusahaan->id}" => 'fileSearchStores/store-2',
    ]);
    expect($modul1->fresh())
        ->status_ai->toBe('siap')
        ->id_di_layanan_ai->toBe('fileSearchStores/store-1/documents/dok-1');
    expect($sop->fresh()->id_di_layanan_ai)->toBe('fileSearchStores/store-2/documents/dok-1');

    Http::assertSent(fn (Request $r) => str_ends_with($r->url(), 'fileSearchStores/store-1:importFile')
        && $r['customMetadata'] === [['key' => 'dokumen_id', 'stringValue' => (string) $modul2->id]]);
    Http::assertSent(fn (Request $r) => $r->url() === 'https://unggah.test/sesi'
        && $r->hasHeader('X-Goog-Upload-Command', 'upload, finalize'));
});

test('sinkron yang gagal menandai dokumen gagal beserta pesannya', function () {
    Http::fake(['*/v1beta/fileSearchStores' => Http::response(['error' => ['message' => 'quota']], 500)]);
    $dokumen = app(DokumenKnowledgeBaseService::class)
        ->simpanSekolah(UploadedFile::fake()->create('modul.pdf', 10), 'TKJ', $this->guru);
    $job = new SinkronDokumenKnowledgeBase($dokumen);

    try {
        $job->handle(app(AiMentorService::class));
    } catch (Throwable $e) {
        $job->failed($e);
    }

    expect($dokumen->fresh())
        ->status_ai->toBe('gagal')
        ->pesan_error_ai->toBe('Layanan AI gagal membuat penyimpanan dokumen. Silakan coba lagi nanti.');

    $this->actingAs($this->guru)->get(route('guru.dokumen.index'))
        ->assertInertia(fn ($page) => $page->where('dokumen.0.status_ai', 'gagal'));
});

test('hapus dokumen ikut menghapus dari Gemini; dokumen yang sudah hilang diabaikan', function () {
    $dokumen = app(DokumenKnowledgeBaseService::class)
        ->simpanSekolah(UploadedFile::fake()->create('modul.pdf', 10), 'TKJ', $this->guru);
    $dokumen->update(['id_di_layanan_ai' => 'fileSearchStores/s/documents/d', 'status_ai' => 'siap']);

    $this->actingAs($this->guru)->delete(route('guru.dokumen.destroy', $dokumen))->assertRedirect();

    Queue::assertPushed(HapusDokumenAi::class, fn (HapusDokumenAi $job) => $job->namaDokumen === 'fileSearchStores/s/documents/d');

    Http::fake(['*/v1beta/fileSearchStores/s/documents/d*' => Http::response([], 404)]);
    (new HapusDokumenAi('fileSearchStores/s/documents/d'))->handle(app(AiMentorService::class));
    Http::assertSent(fn (Request $r) => $r->method() === 'DELETE' && $r['force'] === 'true');
});
