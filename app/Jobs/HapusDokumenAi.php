<?php

namespace App\Jobs;

use App\Services\AiMentorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Hapus dokumen dari Gemini File Search setelah dihapus di website.
 */
class HapusDokumenAi implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [30, 120];

    public function __construct(public string $namaDokumen) {}

    public function handle(AiMentorService $ai): void
    {
        $ai->hapusDokumen($this->namaDokumen);
    }
}
