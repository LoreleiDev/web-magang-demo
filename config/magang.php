<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Program Keahlian
    |--------------------------------------------------------------------------
    |
    | Daftar tetap program keahlian (CLAUDE.md keputusan 13 no. 15). Dipakai
    | di semua form dan untuk membatasi data per program keahlian. Kunci
    | disimpan di database, nilai ditampilkan di antarmuka. Jangan mengubah
    | kunci yang sudah dipakai data.
    |
    */

    'program_keahlian' => [
        'TKJ' => 'Teknik Komputer dan Jaringan',
        'RPL' => 'Rekayasa Perangkat Lunak',
        'TKR' => 'Teknik Kendaraan Ringan',
        'AKL' => 'Akuntansi dan Keuangan Lembaga',
    ],

    /*
    |--------------------------------------------------------------------------
    | Batas Lulus Kuis
    |--------------------------------------------------------------------------
    */

    'skor_lulus' => 75,

];
