import type { Media } from '@/components/media-embed';

export type MateriRingkas = {
    id: number;
    judul: string;
    terverifikasi_industri: boolean;
    kompetensi: string;
    program_keahlian: string;
    program_keahlian_nama: string;
    pembuat: string;
    diubah_terakhir: string | null;
};

export type LangkahMateri = {
    urutan: number;
    jenis: string;
    judul: string;
    konten_teks: string | null;
    media: Media[];
};

export type SoalKuis = {
    id: number;
    pertanyaan: string;
    pilihan: string[];
    jawaban_benar?: number;
    pembahasan?: string | null;
};

export type MateriDetail = MateriRingkas & {
    aktivitas_industri: string;
    langkah: LangkahMateri[];
    soal: SoalKuis[];
};

export type RiwayatVerifikasi = {
    id: number;
    hasil: 'diverifikasi' | 'belum_sesuai';
    hasil_label: string;
    masukan: string | null;
    pemeriksa: string;
    perusahaan: string;
    tanggal: string | null;
};
