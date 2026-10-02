export type StatusKompetensi =
    | 'belum_dipelajari'
    | 'sedang_dipelajari'
    | 'sedang_dipraktikkan'
    | 'menunggu_verifikasi'
    | 'terverifikasi';

export type WarnaGap = 'tinggi' | 'sedang' | 'aman';

/** Satu baris peta kompetensi siswa (KompetensiPresenter). */
export type BarisKompetensi = {
    kompetensi_id: number;
    progres_id: number | null;
    nama_kompetensi_sekolah: string;
    aktivitas_kompetensi_industri: string;
    target_level: number;
    level: number;
    level_diisi: boolean;
    gap: number;
    warna: WarnaGap;
    status: StatusKompetensi;
    status_label: string;
    level_diisi_pada: string | null;
    diverifikasi_oleh: string | null;
    diverifikasi_pada: string | null;
};

export type RingkasanProgres = {
    total: number;
    dikuasai: number;
    gap: number;
    terverifikasi: number;
    persen: number;
};

/** Baris daftar siswa untuk guru/industri (RingkasanSiswaService::baris). */
export type RingkasanSiswa = {
    id: number;
    nama: string;
    id_siswa: string | null;
    program_keahlian: string | null;
    unit_kerja: string | null;
    status_aktif: boolean;
    progres: RingkasanProgres;
    kompetensi_utama: string | null;
    paling_dikuasai: {
        nama: string;
        level: number;
        terverifikasi: boolean;
    } | null;
    jumlah_logbook: number;
    logbook_terakhir: string | null;
    rata_skor: number | null;
    aktivitas_terakhir: {
        jenis: 'logbook' | 'kuis';
        keterangan: string;
        tanggal: string;
    } | null;
};

export type Logbook = {
    id: number;
    tanggal: string;
    siswa: string;
    kelompok: string | null;
    aktivitas: string;
    peralatan_software: string | null;
    sudah_dipahami: string | null;
    baru_ditemui: string | null;
    kesulitan: string | null;
    pengetahuan_sekolah_digunakan: string | null;
    ingin_dipelajari: string | null;
    ada_bukti: boolean;
    url_bukti?: string | null;
    sudah_dianalisis: boolean;
};

export type HasilAssessment = {
    id: number;
    materi: string;
    skor: number;
    lulus: boolean;
    tanggal: string;
};
