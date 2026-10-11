<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Batas Lulus Kuis
    |--------------------------------------------------------------------------
    */

    'skor_lulus' => 75,

    /*
    |--------------------------------------------------------------------------
    | Dokumen Knowledge Base
    |--------------------------------------------------------------------------
    |
    | Jenis & ukuran file dokumen sekolah/industri (KB). Disimpan di disk
    | privat "local"; dikirim ke Gemini di Tahap 6.
    |
    */

    'dokumen_kb' => [
        'ekstensi' => ['pdf', 'docx', 'txt'],
        'maks_kb' => 20480,
    ],

    /*
     * Proxy yang dipercaya (header X-Forwarded-*). Bawaan hanya 127.0.0.1/::1 (mis. ngrok),
     * agar IP klien tidak bisa dipalsukan untuk mengakali rate limit login. Isi
     * TRUSTED_PROXIES (pisahkan dengan koma) jika di balik load balancer/CDN.
     */
    'trusted_proxies' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('TRUSTED_PROXIES', '127.0.0.1,::1')),
    ))),

    /*
     * Akun superadmin pertama yang dibuat DatabaseSeeder. Password WAJIB diisi
     * lewat .env (ADMIN_PASSWORD), jangan ditulis di kode/repository.
     */
    'admin' => [
        'nama' => env('ADMIN_NAME', 'Admin Sekolah'),
        'email' => env('ADMIN_EMAIL', 'admin@magangbridge.com'),
        'password' => env('ADMIN_PASSWORD'),
    ],

];
