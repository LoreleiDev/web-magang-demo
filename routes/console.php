<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Untuk hosting tanpa proses latar belakang permanen (mis. shared hosting Hostinger):
// cron "php artisan schedule:run" tiap menit akan memproses antrean email & sinkron
// dokumen AI. Di server dengan Supervisor (queue:work terus-menerus) ini tetap aman.
Schedule::command('queue:work --stop-when-empty --max-time=55 --tries=3')
    ->everyMinute()
    ->withoutOverlapping(5);

// Bersihkan catatan job gagal yang lebih dari 7 hari agar tabel tidak terus membesar.
Schedule::command('queue:prune-failed --hours=168')->daily();
