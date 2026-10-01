<?php

namespace App\Support;

use App\Models\LangkahMateri;
use App\Models\Materi;
use App\Models\MediaMateri;
use App\Models\SoalKuis;
use App\Models\VerifikasiMateri;

/**
 * Bentuk data materi untuk frontend, dipakai semua role yang membaca materi.
 */
final class MateriPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function ringkas(Materi $materi): array
    {
        $program = config('magang.program_keahlian');

        return [
            'id' => $materi->id,
            'judul' => $materi->judul,
            'terverifikasi_industri' => $materi->terverifikasi_industri,
            'kompetensi' => $materi->kompetensi->nama_kompetensi_sekolah,
            'program_keahlian' => $materi->kompetensi->program_keahlian,
            'program_keahlian_nama' => $program[$materi->kompetensi->program_keahlian] ?? $materi->kompetensi->program_keahlian,
            'pembuat' => $materi->pembuat->name,
            'diubah_terakhir' => $materi->diubah_terakhir?->toIso8601String(),
        ];
    }

    /**
     * Materi lengkap: 8 langkah beserta media dan kuis.
     * Kunci jawaban & pembahasan hanya dikirim jika $denganKunci (bukan untuk siswa yang sedang mengerjakan).
     *
     * @return array<string, mixed>
     */
    public static function detail(Materi $materi, bool $denganKunci): array
    {
        $materi->loadMissing(['kompetensi', 'pembuat', 'langkah.media', 'kuis.soal']);

        return [
            ...self::ringkas($materi),
            'aktivitas_industri' => $materi->kompetensi->aktivitas_kompetensi_industri,
            'langkah' => $materi->langkah->map(fn (LangkahMateri $l) => [
                'urutan' => $l->urutan,
                'jenis' => $l->jenis->value,
                'judul' => $l->jenis->label(),
                'konten_teks' => $l->konten_teks,
                'media' => $l->media->map(fn (MediaMateri $m) => self::media($m))->values(),
            ])->values(),
            'soal' => $materi->kuis?->soal->map(fn (SoalKuis $s) => [
                'id' => $s->id,
                'pertanyaan' => $s->pertanyaan,
                'pilihan' => $s->pilihan,
                ...($denganKunci ? [
                    'jawaban_benar' => $s->jawaban_benar,
                    'pembahasan' => $s->pembahasan,
                ] : []),
            ])->values() ?? [],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function riwayatVerifikasi(Materi $materi): array
    {
        return $materi->verifikasi()
            ->with(['pemeriksa:id,name', 'perusahaan:id,nama'])
            ->latest()
            ->get()
            ->map(fn (VerifikasiMateri $v) => [
                'id' => $v->id,
                'hasil' => $v->hasil->value,
                'hasil_label' => $v->hasil->label(),
                'masukan' => $v->masukan,
                'pemeriksa' => $v->pemeriksa->name,
                'perusahaan' => $v->perusahaan->nama,
                'tanggal' => $v->created_at?->toIso8601String(),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public static function media(MediaMateri $media): array
    {
        return [
            'jenis' => $media->jenis->value,
            'url' => $media->url,
            'keterangan' => $media->keterangan,
            'url_embed' => MediaLink::urlEmbed($media->jenis, $media->id_media),
            'url_buka' => MediaLink::urlBuka($media->jenis, $media->id_media),
        ];
    }
}
