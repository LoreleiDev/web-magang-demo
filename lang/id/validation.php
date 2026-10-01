<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Pesan Validasi (Bahasa Indonesia)
    |--------------------------------------------------------------------------
    |
    | Aturan yang tidak tercantum di sini memakai pesan bahasa Inggris dari
    | lang/en/validation.php sebagai cadangan.
    |
    */

    'accepted' => ':Attribute harus disetujui.',
    'active_url' => ':Attribute bukan URL yang valid.',
    'after' => ':Attribute harus tanggal setelah :date.',
    'after_or_equal' => ':Attribute harus tanggal yang sama atau setelah :date.',
    'alpha' => ':Attribute hanya boleh berisi huruf.',
    'alpha_dash' => ':Attribute hanya boleh berisi huruf, angka, tanda hubung, dan garis bawah.',
    'alpha_num' => ':Attribute hanya boleh berisi huruf dan angka.',
    'array' => ':Attribute harus berupa daftar.',
    'before' => ':Attribute harus tanggal sebelum :date.',
    'before_or_equal' => ':Attribute harus tanggal yang sama atau sebelum :date.',
    'between' => [
        'array' => ':Attribute harus berisi :min sampai :max item.',
        'file' => 'Ukuran :attribute harus :min sampai :max kilobyte.',
        'numeric' => ':Attribute harus bernilai :min sampai :max.',
        'string' => ':Attribute harus berisi :min sampai :max karakter.',
    ],
    'boolean' => ':Attribute harus bernilai ya atau tidak.',
    'confirmed' => 'Konfirmasi :attribute tidak cocok.',
    'date' => ':Attribute bukan tanggal yang valid.',
    'date_equals' => ':Attribute harus tanggal :date.',
    'date_format' => ':Attribute tidak sesuai format :format.',
    'different' => ':Attribute dan :other harus berbeda.',
    'digits' => ':Attribute harus :digits digit.',
    'digits_between' => ':Attribute harus :min sampai :max digit.',
    'distinct' => ':Attribute memiliki nilai yang sama dengan isian lain.',
    'email' => ':Attribute harus berupa alamat email yang valid.',
    'exists' => ':Attribute yang dipilih tidak ditemukan.',
    'extensions' => ':Attribute harus berupa file dengan jenis: :values.',
    'file' => ':Attribute harus berupa file.',
    'filled' => ':Attribute wajib diisi.',
    'gt' => [
        'array' => ':Attribute harus berisi lebih dari :value item.',
        'file' => 'Ukuran :attribute harus lebih dari :value kilobyte.',
        'numeric' => ':Attribute harus lebih dari :value.',
        'string' => ':Attribute harus lebih dari :value karakter.',
    ],
    'gte' => [
        'array' => ':Attribute harus berisi minimal :value item.',
        'file' => 'Ukuran :attribute minimal :value kilobyte.',
        'numeric' => ':Attribute minimal :value.',
        'string' => ':Attribute minimal :value karakter.',
    ],
    'image' => ':Attribute harus berupa gambar.',
    'in' => ':Attribute yang dipilih tidak valid.',
    'in_array' => ':Attribute tidak ada di :other.',
    'integer' => ':Attribute harus berupa bilangan bulat.',
    'list' => ':Attribute harus berupa daftar.',
    'lt' => [
        'array' => ':Attribute harus berisi kurang dari :value item.',
        'file' => 'Ukuran :attribute harus kurang dari :value kilobyte.',
        'numeric' => ':Attribute harus kurang dari :value.',
        'string' => ':Attribute harus kurang dari :value karakter.',
    ],
    'lte' => [
        'array' => ':Attribute maksimal berisi :value item.',
        'file' => 'Ukuran :attribute maksimal :value kilobyte.',
        'numeric' => ':Attribute maksimal :value.',
        'string' => ':Attribute maksimal :value karakter.',
    ],
    'max' => [
        'array' => ':Attribute maksimal berisi :max item.',
        'file' => 'Ukuran :attribute maksimal :max kilobyte.',
        'numeric' => ':Attribute maksimal :max.',
        'string' => ':Attribute maksimal :max karakter.',
    ],
    'mimes' => ':Attribute harus berupa file dengan jenis: :values.',
    'mimetypes' => ':Attribute harus berupa file dengan jenis: :values.',
    'min' => [
        'array' => ':Attribute minimal berisi :min item.',
        'file' => 'Ukuran :attribute minimal :min kilobyte.',
        'numeric' => ':Attribute minimal :min.',
        'string' => ':Attribute minimal :min karakter.',
    ],
    'not_in' => ':Attribute yang dipilih tidak valid.',
    'numeric' => ':Attribute harus berupa angka.',
    'password' => [
        'letters' => ':Attribute harus berisi minimal satu huruf.',
        'mixed' => ':Attribute harus berisi huruf besar dan huruf kecil.',
        'numbers' => ':Attribute harus berisi minimal satu angka.',
        'symbols' => ':Attribute harus berisi minimal satu simbol.',
        'uncompromised' => ':Attribute ini pernah bocor di internet. Silakan pilih :attribute lain.',
    ],
    'present' => ':Attribute harus ada.',
    'prohibited' => ':Attribute tidak boleh diisi.',
    'regex' => 'Format :attribute tidak valid.',
    'required' => ':Attribute wajib diisi.',
    'required_if' => ':Attribute wajib diisi jika :other adalah :value.',
    'required_unless' => ':Attribute wajib diisi kecuali :other adalah :values.',
    'required_with' => ':Attribute wajib diisi jika :values diisi.',
    'required_without' => ':Attribute wajib diisi jika :values tidak diisi.',
    'same' => ':Attribute dan :other harus sama.',
    'size' => [
        'array' => ':Attribute harus berisi :size item.',
        'file' => 'Ukuran :attribute harus :size kilobyte.',
        'numeric' => ':Attribute harus bernilai :size.',
        'string' => ':Attribute harus berisi :size karakter.',
    ],
    'string' => ':Attribute harus berupa teks.',
    'unique' => ':Attribute sudah dipakai.',
    'uploaded' => ':Attribute gagal diunggah.',
    'url' => ':Attribute harus berupa URL yang valid.',

    /*
    |--------------------------------------------------------------------------
    | Nama Atribut
    |--------------------------------------------------------------------------
    */

    'custom' => [],

    'attributes' => [
        'name' => 'nama',
        'email' => 'email',
        'password' => 'kata sandi',
        'role' => 'peran',
        'id_siswa' => 'ID siswa (NIS)',
        'program_keahlian' => 'program keahlian',
        'unit_kerja' => 'unit kerja',
        'kelompok_id' => 'kelompok magang',
        'perusahaan_id' => 'perusahaan',
        'nama_kelompok' => 'nama kelompok',
        'guru_pembimbing_id' => 'guru pembimbing',
        'periode_mulai' => 'tanggal mulai',
        'periode_selesai' => 'tanggal selesai',
        'nama' => 'nama',
        'alamat' => 'alamat',
        'bidang_usaha' => 'bidang usaha',
        'daftar_unit_kerja' => 'daftar unit kerja',
        'jabatan' => 'jabatan',
        'nip' => 'NIP',
        'nama_kompetensi_sekolah' => 'kompetensi sekolah',
        'aktivitas_kompetensi_industri' => 'aktivitas industri',
        'target_level' => 'target level',
        'level_siswa' => 'level siswa',
        'judul' => 'judul',
        'masukan' => 'masukan',
        'tanggal' => 'tanggal',
        'aktivitas' => 'aktivitas',
        'bukti_kegiatan' => 'bukti kegiatan',
        'file' => 'file',
    ],

];
