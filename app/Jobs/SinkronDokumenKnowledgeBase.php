<?php

namespace App\Jobs;

use App\Models\DokumenKnowledgeBase;
use App\Services\AiMentorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;
use Throwable;

/**
 * Teruskan dokumen knowledge base ke Gemini File Search (bagian 9.3) lewat queue,
 * agar halaman guru/admin tidak menunggu proses unggah & indeks.
 */
class SinkronDokumenKnowledgeBase implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 300;

    /** @var list<int> */
    public array $backoff = [30, 120];

    public function __construct(public DokumenKnowledgeBase $dokumen) {}

    public function handle(AiMentorService $ai): void
    {
        $ai->sinkronDokumen($this->dokumen);
    }

    public function failed(?Throwable $e): void
    {
        $this->dokumen->update([
            'status_ai' => 'gagal',
            'pesan_error_ai' => Str::limit($e?->getMessage() ?? 'Gagal mengirim dokumen ke AI.', 250),
        ]);
    }
}
