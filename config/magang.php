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
     * Akun superadmin pertama yang dibuat DatabaseSeeder. Password WAJIB diisi
     * lewat .env (ADMIN_PASSWORD), jangan ditulis di kode/repository.
     */
    'admin' => [
        'nama' => env('ADMIN_NAME', 'Admin Sekolah'),
        'email' => env('ADMIN_EMAIL', 'admin@magangbridge.com'),
        'password' => env('ADMIN_PASSWORD'),
    ],

];
