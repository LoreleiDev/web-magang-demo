<?php

namespace Tests;

use App\Jobs\HapusDokumenAi;
use App\Jobs\SinkronDokumenKnowledgeBase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Test backend tidak bergantung pada hasil `npm run build`.
        $this->withoutVite();

        // Test tidak boleh memanggil Gemini sungguhan; setiap request HTTP harus di-fake
        // (SSR Inertia dimatikan karena ia juga memakai HTTP client).
        Http::preventStrayRequests();
        config(['inertia.ssr.enabled' => false]);

        // Sinkron dokumen ke Gemini tidak dijalankan otomatis (queue "sync" di test);
        // job-nya diuji langsung di tests/Feature/AiMentor.
        Queue::fake([SinkronDokumenKnowledgeBase::class, HapusDokumenAi::class]);
    }
}
