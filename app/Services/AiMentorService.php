<?php

namespace App\Services;

use App\Exceptions\AiMentorException;
use App\Models\DokumenKnowledgeBase;
use App\Models\KnowledgeBaseStore;
use App\Models\Kompetensi;
use App\Models\Logbook;
use App\Models\Materi;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Satu-satunya tempat pemanggilan Google Gemini (CLAUDE.md bagian 9.2).
 *
 * - Chat AI Mentor dengan system prompt bagian 9.4.
 * - Analisis logbook dengan output JSON tervalidasi (bagian 9.5).
 * - Knowledge base memakai Gemini File Search (bagian 9.3): satu store per cakupan
 *   ("sekolah:{program}", "industri:{perusahaan_id}"), sehingga siswa hanya memakai
 *   dokumen program keahlian dan perusahaannya sendiri.
 */
class AiMentorService
{
    private const JUMLAH_RIWAYAT = 12;

    private const MIME = [
        'pdf' => 'application/pdf',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'txt' => 'text/plain',
    ];

    public function __construct(private PendampinganSiswaService $pendampingan) {}

    // ------------------------------------------------------------------
    // Chat AI Mentor
    // ------------------------------------------------------------------

    /**
     * @param  list<array{peran: string, isi: string}>  $riwayat  Percakapan sebelumnya.
     * @return array{teks: string, sumber: list<string>}
     */
    public function jawab(User $siswa, array $riwayat, string $pesan, ?Kompetensi $konteks = null): array
    {
        if ($konteks !== null) {
            $baris = $this->pendampingan->peta($siswa)->first(fn (array $b) => $b['kompetensi']->id === $konteks->id);
            $pesan = "[Konteks: saya ingin meningkatkan kompetensi \"{$konteks->nama_kompetensi_sekolah}\" "
                ."(aktivitas industri: {$konteks->aktivitas_kompetensi_industri}; level saya ".($baris['level'] ?? 1)
                .", target {$konteks->target_level}).]\n".$pesan;
        }

        $contents = collect($riwayat)
            ->take(-self::JUMLAH_RIWAYAT)
            ->map(fn (array $p) => [
                'role' => $p['peran'] === 'siswa' ? 'user' : 'model',
                'parts' => [['text' => $p['isi']]],
            ])
            ->push(['role' => 'user', 'parts' => [['text' => $pesan]]])
            ->values()
            ->all();

        $respons = $this->generate([
            'systemInstruction' => ['parts' => [['text' => $this->systemPrompt($siswa)]]],
            'contents' => $contents,
            ...$this->toolKnowledgeBase($siswa),
        ]);

        return [
            'teks' => $this->teks($respons),
            'sumber' => $this->sumber($respons, $siswa),
        ];
    }

    // ------------------------------------------------------------------
    // Analisis logbook (bagian 9.5)
    // ------------------------------------------------------------------

    /**
     * @return array{kompetensi_digunakan: list<string>, kompetensi_baru: list<string>, kemungkinan_learning_gap: list<string>, rekomendasi_materi: list<string>, pertanyaan_refleksi: list<string>}
     *
     * @throws AiMentorException jika AI gagal atau balasannya bukan JSON yang valid.
     */
    public function analisisLogbook(Logbook $logbook): array
    {
        $siswa = $logbook->siswa;

        $daftarKompetensi = $this->pendampingan->peta($siswa)
            ->map(fn (array $b) => "- {$b['kompetensi']->nama_kompetensi_sekolah} (industri: {$b['kompetensi']->aktivitas_kompetensi_industri})")
            ->implode("\n");

        $isi = collect([
            'Tanggal' => $logbook->tanggal->toDateString(),
            'Aktivitas hari ini' => $logbook->aktivitas,
            'Peralatan/software' => $logbook->peralatan_software,
            'Yang sudah dipahami' => $logbook->sudah_dipahami,
            'Yang baru ditemui' => $logbook->baru_ditemui,
            'Kesulitan' => $logbook->kesulitan,
            'Pengetahuan sekolah yang digunakan' => $logbook->pengetahuan_sekolah_digunakan,
            'Ingin dipelajari' => $logbook->ingin_dipelajari,
        ])->filter()->map(fn ($v, $k) => "{$k}: {$v}")->implode("\n");

        $prompt = <<<PROMPT
            Analisis logbook harian siswa berikut berdasarkan daftar kompetensi
            {$daftarKompetensi}
            dan dokumen knowledge base.

            Logbook:
            {$isi}

            Balas HANYA dengan JSON:
            {
              "kompetensi_digunakan": ["..."],
              "kompetensi_baru": ["..."],
              "kemungkinan_learning_gap": ["..."],
              "rekomendasi_materi": ["..."],
              "pertanyaan_refleksi": ["...", "..."]
            }
            PROMPT;

        // Aturan sumber informasi & keselamatan sama dengan 9.4 (lewat system prompt).
        $respons = $this->generate([
            'systemInstruction' => ['parts' => [['text' => $this->systemPrompt($siswa)]]],
            'contents' => [['role' => 'user', 'parts' => [['text' => $prompt]]]],
            ...$this->toolKnowledgeBase($siswa),
        ]);

        return $this->validasiAnalisis($this->teks($respons));
    }

    /**
     * Ambil & validasi JSON hasil analisis. Backend wajib memvalidasi sebelum disimpan.
     *
     * @return array{kompetensi_digunakan: list<string>, kompetensi_baru: list<string>, kemungkinan_learning_gap: list<string>, rekomendasi_materi: list<string>, pertanyaan_refleksi: list<string>}
     */
    public function validasiAnalisis(string $teks): array
    {
        $awal = strpos($teks, '{');
        $akhir = strrpos($teks, '}');
        $data = $awal === false || $akhir === false ? null : json_decode(substr($teks, $awal, $akhir - $awal + 1), true);

        $kunci = ['kompetensi_digunakan', 'kompetensi_baru', 'kemungkinan_learning_gap', 'rekomendasi_materi', 'pertanyaan_refleksi'];
        $aturan = [];
        foreach ($kunci as $k) {
            $aturan[$k] = ['present', 'array', 'max:10'];
            $aturan["{$k}.*"] = ['string', 'max:500'];
        }
        $aturan['pertanyaan_refleksi'][] = 'min:1';

        if (! is_array($data) || Validator::make($data, $aturan)->fails()) {
            Log::warning('Analisis logbook: balasan AI bukan JSON yang valid.', ['balasan' => Str::limit($teks, 500)]);

            throw new AiMentorException('AI belum bisa menganalisis logbook ini. Silakan coba lagi sebentar lagi.');
        }

        /** @var array{kompetensi_digunakan: list<string>, kompetensi_baru: list<string>, kemungkinan_learning_gap: list<string>, rekomendasi_materi: list<string>, pertanyaan_refleksi: list<string>} */
        return collect($kunci)->mapWithKeys(fn (string $k) => [
            $k => array_values(array_map(fn ($v) => trim((string) $v), $data[$k])),
        ])->all();
    }

    // ------------------------------------------------------------------
    // Knowledge base (Gemini File Search)
    // ------------------------------------------------------------------

    /**
     * Unggah dokumen ke store cakupannya. Dipanggil dari queue job.
     */
    public function sinkronDokumen(DokumenKnowledgeBase $dokumen): void
    {
        $store = $this->storeUntuk(KnowledgeBaseStore::cakupanDokumen($dokumen));
        $isi = Storage::disk('local')->get($dokumen->path_file);
        $ekstensi = strtolower(pathinfo($dokumen->nama_file, PATHINFO_EXTENSION));
        $mime = self::MIME[$ekstensi] ?? 'application/octet-stream';

        if ($isi === null) {
            throw new AiMentorException('File dokumen tidak ditemukan di server.');
        }

        // 1) Upload ke Files API (protokol resumable).
        $mulai = $this->http()
            ->withHeaders([
                'X-Goog-Upload-Protocol' => 'resumable',
                'X-Goog-Upload-Command' => 'start',
                'X-Goog-Upload-Header-Content-Length' => (string) strlen($isi),
                'X-Goog-Upload-Header-Content-Type' => $mime,
            ])
            ->post('/upload/v1beta/files', ['file' => ['display_name' => $dokumen->nama_file]]);
        $this->pastikanBerhasil($mulai, 'memulai unggah dokumen');

        $urlUnggah = $mulai->header('X-Goog-Upload-URL');
        if ($urlUnggah === '') {
            throw new AiMentorException('Gemini tidak memberikan alamat unggah.');
        }

        $unggah = Http::timeout(120)
            ->withHeaders(['X-Goog-Upload-Offset' => '0', 'X-Goog-Upload-Command' => 'upload, finalize'])
            ->withBody($isi, $mime)
            ->post($urlUnggah);
        $this->pastikanBerhasil($unggah, 'mengunggah dokumen');
        $namaFile = (string) $unggah->json('file.name');

        // 2) Impor ke store dengan metadata dokumen_id (dipakai untuk menyebut sumber).
        $operasi = $this->http()->post("/v1beta/{$store}:importFile", [
            'fileName' => $namaFile,
            'customMetadata' => [['key' => 'dokumen_id', 'stringValue' => (string) $dokumen->id]],
        ]);
        $this->pastikanBerhasil($operasi, 'mengimpor dokumen');

        $hasil = $this->tungguOperasi($operasi->json());
        $namaDokumen = (string) data_get($hasil, 'response.documentName');

        $dokumen->update([
            'id_di_layanan_ai' => $namaDokumen === '' ? null : "{$store}/documents/{$namaDokumen}",
            'status_ai' => 'siap',
            'pesan_error_ai' => null,
        ]);

        // File sementara di Files API tidak diperlukan lagi setelah diimpor.
        $this->http()->delete("/v1beta/{$namaFile}");
    }

    /**
     * Hapus dokumen dari File Search. Diabaikan jika dokumen sudah tidak ada.
     */
    public function hapusDokumen(string $namaDokumen): void
    {
        $respons = $this->http()->delete("/v1beta/{$namaDokumen}", ['force' => 'true']);

        if ($respons->failed() && $respons->status() !== 404) {
            $this->pastikanBerhasil($respons, 'menghapus dokumen');
        }
    }

    // ------------------------------------------------------------------
    // Bagian dalam
    // ------------------------------------------------------------------

    /**
     * System prompt bagian 9.4, variabel diisi dari data siswa yang login.
     */
    public function systemPrompt(User $siswa): string
    {
        $konteks = $this->pendampingan->konteks($siswa);
        $gap = $this->pendampingan->peta($siswa)
            ->filter(fn (array $b) => $b['gap'] > 0)
            ->sortByDesc('gap')
            ->take(3)
            ->map(fn (array $b) => "{$b['kompetensi']->nama_kompetensi_sekolah} (level {$b['level']}, target {$b['kompetensi']->target_level})")
            ->implode('; ');
        $materi = $this->pendampingan->materiProgram($siswa)
            ->map(fn (Materi $m) => "\"{$m->judul}\"")
            ->implode(', ');
        $hari = $konteks['hari_ke'] ?? match ($konteks['status_magang']) {
            'belum_mulai' => '0 (belum dimulai)',
            'selesai' => "{$konteks['total_hari']} (sudah selesai)",
            default => '-',
        };

        return str_replace(
            ['{nama_siswa}', '{program_keahlian}', '{nama_perusahaan}', '{unit_kerja}', '{hari_ke}', '{daftar_kompetensi_gap}', '{daftar_judul_materi}'],
            [
                $siswa->name,
                (string) ($konteks['program_keahlian_nama'] ?? '-'),
                (string) ($konteks['perusahaan'] ?? '-'),
                (string) ($konteks['unit_kerja'] ?? '-'),
                (string) $hari,
                $gap === '' ? 'tidak ada' : $gap,
                $materi === '' ? 'belum ada materi' : $materi,
            ],
            (string) file_get_contents(resource_path('prompts/ai-mentor.txt')),
        )."\n\nFormat teks:\nTampilan chat hanya mendukung teks biasa, **tebal**, dan daftar berawalan \"- \" atau \"1. \". "
            .'Jangan gunakan LaTeX, tabel, atau heading "#"; tulis rumus sebagai teks biasa, misalnya 2^(32 - prefix) - 2.';
    }

    /**
     * Tool File Search hanya untuk store program keahlian & perusahaan siswa ini.
     *
     * @return array<string, mixed>
     */
    private function toolKnowledgeBase(User $siswa): array
    {
        $program = $siswa->programKeahlian();
        $perusahaan = $siswa->perusahaanId();
        $siap = DokumenKnowledgeBase::query()->where('status_ai', 'siap');

        // Hanya cakupan yang benar-benar punya dokumen siap.
        $cakupan = array_filter([
            $program && (clone $siap)->where('jenis', 'sekolah')->where('program_keahlian', $program)->exists()
                ? "sekolah:{$program}" : null,
            $perusahaan && (clone $siap)->where('jenis', 'industri')->where('perusahaan_id', $perusahaan)->exists()
                ? "industri:{$perusahaan}" : null,
        ]);

        $store = $cakupan === [] ? [] : KnowledgeBaseStore::whereIn('cakupan', $cakupan)->pluck('nama_store')->all();

        return $store === [] ? [] : ['tools' => [['file_search' => ['file_search_store_names' => array_values($store)]]]];
    }

    /**
     * Nama dokumen sumber dari grounding File Search, hanya dokumen dalam cakupan siswa.
     *
     * @param  array<string, mixed>  $respons
     * @return list<string>
     */
    private function sumber(array $respons, User $siswa): array
    {
        $ids = [];
        foreach ((array) data_get($respons, 'candidates.0.groundingMetadata.groundingChunks', []) as $chunk) {
            foreach ((array) data_get($chunk, 'retrievedContext.customMetadata', []) as $meta) {
                if (data_get($meta, 'key') === 'dokumen_id' && is_numeric(data_get($meta, 'stringValue'))) {
                    $ids[] = (int) data_get($meta, 'stringValue');
                }
            }
        }
        $ids = array_values(array_unique($ids));

        if ($ids === []) {
            return [];
        }

        return array_values(DokumenKnowledgeBase::query()
            ->whereIn('id', $ids)
            ->where(fn ($q) => $q
                ->where(fn ($q) => $q->where('jenis', 'sekolah')->where('program_keahlian', $siswa->programKeahlian()))
                ->orWhere(fn ($q) => $q->where('jenis', 'industri')->where('perusahaan_id', $siswa->perusahaanId())))
            ->pluck('nama_file')
            ->unique()
            ->all());
    }

    /**
     * Panggil generateContent; jika model utama sibuk, coba model cadangan.
     *
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    private function generate(array $body): array
    {
        $model = array_unique(array_filter([config('services.gemini.model'), config('services.gemini.model_cadangan')]));

        foreach ($model as $m) {
            try {
                $respons = $this->http()->post("/v1beta/models/{$m}:generateContent", $body);
            } catch (ConnectionException $e) {
                Log::warning('Gemini tidak bisa dihubungi.', ['model' => $m, 'error' => $e->getMessage()]);

                continue;
            }

            if (in_array($respons->status(), [429, 500, 503], true)) {
                Log::warning('Gemini sibuk, mencoba model lain.', ['model' => $m, 'status' => $respons->status()]);

                continue;
            }

            $this->pastikanBerhasil($respons, 'menjawab');

            return (array) $respons->json();
        }

        throw new AiMentorException('AI Mentor sedang sibuk. Silakan coba lagi beberapa saat lagi.');
    }

    /**
     * @param  array<string, mixed>  $respons
     */
    private function teks(array $respons): string
    {
        $teks = '';
        foreach ((array) data_get($respons, 'candidates.0.content.parts', []) as $bagian) {
            // Bagian "thought" (proses berpikir model) tidak ditampilkan.
            if (! data_get($bagian, 'thought', false) && is_string(data_get($bagian, 'text'))) {
                $teks .= data_get($bagian, 'text');
            }
        }

        if (trim($teks) === '') {
            throw new AiMentorException('AI Mentor tidak bisa menjawab pesan ini. Coba tulis pertanyaan dengan kalimat lain.');
        }

        return trim($teks);
    }

    private function storeUntuk(string $cakupan): string
    {
        $ada = KnowledgeBaseStore::where('cakupan', $cakupan)->value('nama_store');

        if ($ada !== null) {
            return $ada;
        }

        $respons = $this->http()->post('/v1beta/fileSearchStores', [
            'displayName' => 'magangbridge-'.Str::slug(str_replace(':', '-', $cakupan)),
        ]);
        $this->pastikanBerhasil($respons, 'membuat penyimpanan dokumen');

        return KnowledgeBaseStore::create([
            'cakupan' => $cakupan,
            'nama_store' => (string) $respons->json('name'),
        ])->nama_store;
    }

    /**
     * Tunggu operasi impor selesai (maks. ±2 menit).
     *
     * @param  array<string, mixed>  $operasi
     * @return array<string, mixed>
     */
    private function tungguOperasi(array $operasi): array
    {
        for ($i = 0; $i < 40 && ! data_get($operasi, 'done', false); $i++) {
            sleep(3);
            $respons = $this->http()->get('/v1beta/'.data_get($operasi, 'name'));
            $this->pastikanBerhasil($respons, 'memeriksa status impor');
            $operasi = (array) $respons->json();
        }

        if (! data_get($operasi, 'done', false)) {
            throw new AiMentorException('Impor dokumen ke AI terlalu lama.');
        }

        if (data_get($operasi, 'error')) {
            throw new AiMentorException('Gemini menolak dokumen: '.data_get($operasi, 'error.message', 'tidak diketahui'));
        }

        return $operasi;
    }

    private function pastikanBerhasil(Response $respons, string $aksi): void
    {
        if ($respons->successful()) {
            return;
        }

        Log::error("Gemini gagal {$aksi}.", ['status' => $respons->status(), 'body' => Str::limit($respons->body(), 1000)]);

        throw new AiMentorException("Layanan AI gagal {$aksi}. Silakan coba lagi nanti.");
    }

    private function http(): PendingRequest
    {
        $key = (string) config('services.gemini.key');

        if ($key === '') {
            throw new AiMentorException('AI Mentor belum dikonfigurasi. Hubungi admin sekolah.');
        }

        return Http::baseUrl((string) config('services.gemini.base_url'))
            ->withHeaders(['x-goog-api-key' => $key])
            ->timeout((int) config('services.gemini.timeout'))
            ->acceptJson();
    }
}
