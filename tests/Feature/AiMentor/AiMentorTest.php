<?php

use App\Enums\JenisDokumen;
use App\Exceptions\AiMentorException;
use App\Models\ChatAiMentor;
use App\Models\DokumenKnowledgeBase;
use App\Models\KelompokMagang;
use App\Models\KnowledgeBaseStore;
use App\Models\Kompetensi;
use App\Models\Logbook;
use App\Models\Perusahaan;
use App\Models\User;
use App\Services\AiMentorService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->guru = User::factory()->guru('TKJ')->create();
    $this->kelompok = KelompokMagang::factory()->create(['guru_pembimbing_id' => $this->guru->id]);
    $this->siswa = User::factory()->siswa($this->kelompok, 'TKJ', 'Jaringan')->create();
    $this->kompetensi = Kompetensi::factory()->create(['program_keahlian' => 'TKJ']);
    siswaSiap($this->siswa, $this->kompetensi->id);

    $this->dokumen = function (JenisDokumen $jenis, string $nama, array $cakupan, string $status = 'siap') {
        return DokumenKnowledgeBase::create([
            'jenis' => $jenis,
            ...$cakupan,
            'nama_file' => $nama,
            'path_file' => "kb/{$nama}",
            'diunggah_oleh' => $this->guru->id,
            'status_ai' => $status,
        ]);
    };
});

/**
 * Respons generateContent Gemini dengan grounding File Search.
 *
 * @param  list<int>  $dokumenIds
 */
function balasanGemini(string $teks, array $dokumenIds = []): array
{
    return [
        'candidates' => [[
            'content' => ['role' => 'model', 'parts' => [
                ['text' => 'proses berpikir', 'thought' => true],
                ['text' => $teks],
            ]],
            'groundingMetadata' => ['groundingChunks' => array_map(fn (int $id) => [
                'retrievedContext' => ['customMetadata' => [['key' => 'dokumen_id', 'stringValue' => (string) $id]]],
            ], $dokumenIds)],
        ]],
    ];
}

test('siswa bertanya ke AI Mentor; hanya store & sumber program dan perusahaannya yang dipakai', function () {
    $perusahaanLain = Perusahaan::factory()->create();
    $modul = ($this->dokumen)(JenisDokumen::Sekolah, 'modul-tkj.pdf', ['program_keahlian' => 'TKJ']);
    $sop = ($this->dokumen)(JenisDokumen::Industri, 'sop-sendiri.pdf', ['perusahaan_id' => $this->kelompok->perusahaan_id]);
    $sopLain = ($this->dokumen)(JenisDokumen::Industri, 'sop-lain.pdf', ['perusahaan_id' => $perusahaanLain->id]);
    ($this->dokumen)(JenisDokumen::Sekolah, 'modul-rpl.pdf', ['program_keahlian' => 'RPL']);

    KnowledgeBaseStore::create(['cakupan' => 'sekolah:TKJ', 'nama_store' => 'fileSearchStores/tkj']);
    KnowledgeBaseStore::create(['cakupan' => "industri:{$this->kelompok->perusahaan_id}", 'nama_store' => 'fileSearchStores/sendiri']);
    KnowledgeBaseStore::create(['cakupan' => "industri:{$perusahaanLain->id}", 'nama_store' => 'fileSearchStores/lain']);
    KnowledgeBaseStore::create(['cakupan' => 'sekolah:RPL', 'nama_store' => 'fileSearchStores/rpl']);

    // Grounding yang (seharusnya mustahil) menyebut dokumen perusahaan lain tetap disaring.
    Http::fake(['*:generateContent' => Http::response(balasanGemini('**Aktivitas Anda** memasang kabel.', [$modul->id, $sop->id, $sopLain->id]))]);

    $this->actingAs($this->siswa)
        ->postJson(route('siswa.mentor.kirim'), ['pesan' => 'Saya sedang memasang kabel UTP'])
        ->assertOk()
        ->assertJsonPath('balasan.isi', '**Aktivitas Anda** memasang kabel.')
        ->assertJsonPath('balasan.sumber', ['modul-tkj.pdf', 'sop-sendiri.pdf']);

    Http::assertSent(function (Request $r) {
        $store = collect(data_get($r->data(), 'tools.0.file_search.file_search_store_names'))->sort()->values()->all();
        $prompt = data_get($r->data(), 'systemInstruction.parts.0.text');

        return $r->hasHeader('x-goog-api-key', 'kunci-uji')
            && $store === ['fileSearchStores/sendiri', 'fileSearchStores/tkj']
            && str_contains($prompt, "Nama: {$this->siswa->name}")
            && ! preg_match('/\{[a-z_]+\}/', $prompt);
    });

    $chat = ChatAiMentor::where('siswa_id', $this->siswa->id)->sole();
    expect($chat->pesan)->toHaveCount(2)
        ->and($chat->pesan[0])->toBe(['peran' => 'siswa', 'isi' => 'Saya sedang memasang kabel UTP'])
        ->and($chat->pesan[1]['peran'])->toBe('mentor');

    $this->actingAs($this->siswa)->getJson(route('siswa.mentor.riwayat'))->assertJsonCount(2, 'pesan');
    $this->actingAs($this->siswa)->deleteJson(route('siswa.mentor.hapus'))->assertOk();
    expect(ChatAiMentor::count())->toBe(0);
});

test('store tanpa dokumen siap tidak dikirim ke Gemini', function () {
    ($this->dokumen)(JenisDokumen::Sekolah, 'modul.pdf', ['program_keahlian' => 'TKJ'], 'menunggu');
    KnowledgeBaseStore::create(['cakupan' => 'sekolah:TKJ', 'nama_store' => 'fileSearchStores/tkj']);
    Http::fake(['*:generateContent' => Http::response(balasanGemini('Halo'))]);

    $this->actingAs($this->siswa)->postJson(route('siswa.mentor.kirim'), ['pesan' => 'Halo'])->assertOk();

    Http::assertSent(fn (Request $r) => ! array_key_exists('tools', $r->data()));
});

test('konteks "Tanya AI Mentor" dari learning gap ikut dikirim; kompetensi program lain ditolak', function () {
    Http::fake(['*:generateContent' => Http::response(balasanGemini('Baik'))]);

    $this->actingAs($this->siswa)
        ->postJson(route('siswa.mentor.kirim'), ['pesan' => 'Bantu saya', 'kompetensi_id' => $this->kompetensi->id])
        ->assertOk();
    Http::assertSent(fn (Request $r) => str_contains(
        (string) data_get($r->data(), 'contents.0.parts.0.text'),
        $this->kompetensi->nama_kompetensi_sekolah,
    ));

    $rpl = Kompetensi::factory()->create(['program_keahlian' => 'RPL']);
    $this->actingAs($this->siswa)
        ->postJson(route('siswa.mentor.kirim'), ['pesan' => 'Bantu saya', 'kompetensi_id' => $rpl->id])
        ->assertJsonValidationErrors('kompetensi_id');
    $this->actingAs($this->siswa)->postJson(route('siswa.mentor.kirim'), ['pesan' => ''])
        ->assertJsonValidationErrors('pesan');
});

test('model cadangan dipakai saat model utama sibuk; jika semua gagal muncul pesan ramah', function () {
    config(['services.gemini.model' => 'utama', 'services.gemini.model_cadangan' => 'cadangan']);
    Http::fake([
        '*/models/utama:generateContent' => Http::response([], 503),
        '*/models/cadangan:generateContent' => Http::response(balasanGemini('Dari cadangan')),
    ]);

    $this->actingAs($this->siswa)->postJson(route('siswa.mentor.kirim'), ['pesan' => 'Halo'])
        ->assertOk()
        ->assertJsonPath('balasan.isi', 'Dari cadangan');

    // Fake sebelumnya tetap terdaftar, jadi pakai nama model lain.
    config(['services.gemini.model' => 'sibuk-1', 'services.gemini.model_cadangan' => 'sibuk-2']);
    Http::fake(['*/models/sibuk-*' => Http::response([], 429)]);
    ChatAiMentor::query()->delete();

    $this->actingAs($this->siswa)->postJson(route('siswa.mentor.kirim'), ['pesan' => 'Halo'])
        ->assertStatus(503)
        ->assertJsonPath('message', 'AI Mentor sedang sibuk. Silakan coba lagi beberapa saat lagi.');
    expect(ChatAiMentor::count())->toBe(0);
});

test('model utama yang terlalu lama (timeout) dilewati, model cadangan menjawab', function () {
    config([
        'services.gemini.model' => 'lambat',
        'services.gemini.model_cadangan' => 'cepat',
        'services.gemini.timeout' => 25,
        'services.gemini.batas_waktu' => 55,
    ]);
    Http::fake([
        '*/models/lambat:generateContent' => Http::failedConnection('Operation timed out'),
        '*/models/cepat:generateContent' => Http::response(balasanGemini('Dari model cepat')),
    ]);

    $this->actingAs($this->siswa)->postJson(route('siswa.mentor.kirim'), ['pesan' => 'Halo'])
        ->assertOk()
        ->assertJsonPath('balasan.isi', 'Dari model cepat');

    // Setiap model dibatasi waktu tunggu per model, bukan menunggu tanpa batas.
    Http::assertSent(fn (Request $r) => str_contains($r->url(), 'lambat'));
});

test('jika batas waktu total habis, siswa menerima pesan ramah (bukan fatal error)', function () {
    config(['services.gemini.batas_waktu' => 4]);
    Http::fake(['*:generateContent' => Http::response(balasanGemini('Tidak terpakai'))]);

    $this->actingAs($this->siswa)->postJson(route('siswa.mentor.kirim'), ['pesan' => 'Halo'])
        ->assertStatus(503)
        ->assertJsonPath('message', 'AI Mentor sedang sibuk. Silakan coba lagi beberapa saat lagi.');
    Http::assertNothingSent();
});

test('tanpa API key AI Mentor menampilkan pesan belum dikonfigurasi', function () {
    config(['services.gemini.key' => null]);

    $this->actingAs($this->siswa)->postJson(route('siswa.mentor.kirim'), ['pesan' => 'Halo'])
        ->assertStatus(503)
        ->assertJsonPath('message', 'AI Mentor belum dikonfigurasi. Hubungi admin sekolah.');
    Http::assertNothingSent();
});

test('AI Mentor dibatasi 10 pesan per menit per siswa', function () {
    Http::fake(['*:generateContent' => Http::response(balasanGemini('Oke'))]);

    foreach (range(1, 10) as $i) {
        $this->actingAs($this->siswa)->postJson(route('siswa.mentor.kirim'), ['pesan' => "Pesan {$i}"])->assertOk();
    }

    $this->actingAs($this->siswa)->postJson(route('siswa.mentor.kirim'), ['pesan' => 'Lagi'])
        ->assertStatus(429)
        ->assertJsonStructure(['message']);
});

test('hanya siswa yang bisa memakai AI Mentor', function () {
    $lain = [
        User::factory()->superadmin()->create(),
        $this->guru,
        User::factory()->industri(Perusahaan::find($this->kelompok->perusahaan_id))->create(),
    ];

    foreach ($lain as $user) {
        $this->actingAs($user)->getJson(route('siswa.mentor.riwayat'))->assertForbidden();
        $this->actingAs($user)->postJson(route('siswa.mentor.kirim'), ['pesan' => 'Halo'])->assertForbidden();
        $this->actingAs($user)->deleteJson(route('siswa.mentor.hapus'))->assertForbidden();
    }

    Http::assertNothingSent();
});

test('analisis logbook menyimpan JSON yang valid', function () {
    $logbook = Logbook::create([
        'siswa_id' => $this->siswa->id,
        'tanggal' => now()->toDateString(),
        'aktivitas' => 'Konfigurasi VLAN',
    ]);
    $json = [
        'kompetensi_digunakan' => ['Jaringan dasar'],
        'kompetensi_baru' => ['VLAN'],
        'kemungkinan_learning_gap' => [],
        'rekomendasi_materi' => ['Subnetting'],
        'pertanyaan_refleksi' => ['Apa fungsi VLAN?', 'Kapan trunk dipakai?'],
    ];
    Http::fake(['*:generateContent' => Http::response(balasanGemini("```json\n".json_encode($json)."\n```"))]);

    $this->actingAs($this->siswa)
        ->post(route('siswa.logbook.analisis', $logbook))
        ->assertSessionHasNoErrors();

    expect($logbook->fresh()->hasil_analisis_ai)->toBe($json);
    Http::assertSent(fn (Request $r) => str_contains((string) data_get($r->data(), 'contents.0.parts.0.text'), 'Konfigurasi VLAN'));
});

test('balasan analisis yang bukan JSON valid tidak disimpan dan memberi pesan ramah', function () {
    $logbook = Logbook::create([
        'siswa_id' => $this->siswa->id,
        'tanggal' => now()->toDateString(),
        'aktivitas' => 'Konfigurasi VLAN',
    ]);
    Http::fake(['*:generateContent' => Http::response(balasanGemini('Maaf, saya tidak bisa.'))]);

    $this->actingAs($this->siswa)
        ->post(route('siswa.logbook.analisis', $logbook))
        ->assertSessionHasErrors(['analisis' => 'AI belum bisa menganalisis logbook ini. Silakan coba lagi sebentar lagi.']);

    expect($logbook->fresh()->hasil_analisis_ai)->toBeNull();
});

test('validasi JSON analisis menolak kunci hilang atau tipe salah', function (string $teks) {
    app(AiMentorService::class)->validasiAnalisis($teks);
})->throws(AiMentorException::class)->with([
    'bukan json' => ['tidak ada json'],
    'kunci hilang' => ['{"kompetensi_digunakan": []}'],
    'bukan daftar' => ['{"kompetensi_digunakan":"x","kompetensi_baru":[],"kemungkinan_learning_gap":[],"rekomendasi_materi":[],"pertanyaan_refleksi":["?"]}'],
    'refleksi kosong' => ['{"kompetensi_digunakan":[],"kompetensi_baru":[],"kemungkinan_learning_gap":[],"rekomendasi_materi":[],"pertanyaan_refleksi":[]}'],
]);

test('siswa tidak bisa menganalisis logbook siswa lain', function () {
    $lain = siswaSiap(User::factory()->siswa($this->kelompok, 'TKJ', 'Jaringan')->create(), $this->kompetensi->id);
    $logbook = Logbook::create(['siswa_id' => $lain->id, 'tanggal' => now()->toDateString(), 'aktivitas' => 'X']);

    $this->actingAs($this->siswa)->post(route('siswa.logbook.analisis', $logbook))->assertForbidden();
    $this->actingAs($this->guru)->post(route('siswa.logbook.analisis', $logbook))->assertForbidden();
    Http::assertNothingSent();
});
