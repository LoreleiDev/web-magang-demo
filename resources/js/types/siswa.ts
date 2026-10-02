/** Konteks magang siswa (PendampinganSiswaService::konteks). */
export type KonteksSiswa = {
    nama: string;
    id_siswa: string | null;
    program_keahlian: string | null;
    program_keahlian_nama: string | null;
    perusahaan: string | null;
    unit_kerja: string | null;
    kelompok: string | null;
    periode_mulai: string | null;
    periode_selesai: string | null;
    status_magang: 'belum_mulai' | 'berjalan' | 'selesai' | null;
    hari_ke: number | null;
    total_hari: number | null;
    hari_menuju_mulai: number | null;
    kompetensi_fokus_id: number | null;
};

export type StatusKuis = {
    terbaik: number | null;
    percobaan: number;
    lulus: boolean;
    terakhir: string | null;
};

export type Rekomendasi = {
    materi_id: number;
    judul: string;
    level: number;
    terverifikasi_industri: boolean;
    kompetensi_id: number;
    kompetensi: string;
    gap: number;
    warna: 'tinggi' | 'sedang' | 'aman';
};

/** Hasil kuis yang dikirim lewat flash setelah siswa mengirim jawaban. */
export type HasilKuis = {
    materi_id: number;
    skor: number;
    benar: number;
    total: number;
    lulus: boolean;
    nilai_minimal: number;
    level_naik: number | null;
    rincian: {
        soal_id: number;
        dipilih: number | null;
        jawaban_benar: number;
        benar: boolean;
        pembahasan: string | null;
    }[];
};
