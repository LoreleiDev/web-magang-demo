<?php

namespace App\Services;

use App\Enums\JenisLangkah;
use App\Enums\JenisMedia;
use App\Models\Materi;
use App\Models\User;
use App\Support\MediaLink;
use Illuminate\Support\Facades\DB;

/**
 * Menyimpan materi beserta 8 langkah, media link, dan kuisnya.
 */
class MateriService
{
    /**
     * @param  array<string, mixed>  $data  Data tervalidasi dari MateriRequest.
     */
    public function simpan(?Materi $materi, array $data, User $guru): Materi
    {
        return DB::transaction(function () use ($materi, $data, $guru) {
            $materi ??= new Materi(['dibuat_oleh' => $guru->id]);

            // Materi yang diedit perlu diverifikasi ulang oleh industri (bagian 13.1 no. 2).
            $materi->fill([
                'kompetensi_id' => $data['kompetensi_id'],
                'judul' => $data['judul'],
                'level' => $data['level'],
                'nilai_minimal' => $data['nilai_minimal'],
                'diubah_terakhir' => now(),
                'terverifikasi_industri' => false,
            ])->save();

            $this->simpanLangkah($materi, $data['langkah']);
            $this->simpanSoal($materi, $data['soal']);

            return $materi;
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $langkah
     */
    private function simpanLangkah(Materi $materi, array $langkah): void
    {
        $materi->langkah()->delete();

        foreach (JenisLangkah::cases() as $i => $jenis) {
            $isi = $langkah[$i] ?? [];

            $baris = $materi->langkah()->create([
                'urutan' => $jenis->urutan(),
                'jenis' => $jenis,
                'konten_teks' => $isi['konten_teks'] ?? null,
            ]);

            foreach (array_values($isi['media'] ?? []) as $j => $media) {
                $jenisMedia = JenisMedia::from($media['jenis']);

                // ID diambil saat disimpan agar format link berbeda tetap tampil benar (bagian 6.4.1).
                $baris->media()->create([
                    'urutan' => $j + 1,
                    'jenis' => $jenisMedia,
                    'url' => trim($media['url']),
                    'id_media' => MediaLink::ambilId($jenisMedia, $media['url']),
                    'keterangan' => $media['keterangan'] ?? null,
                ]);
            }
        }
    }

    /**
     * Soal diganti seluruhnya; kuis (dan riwayat hasil assessment) tetap.
     *
     * @param  array<int, array<string, mixed>>  $soal
     */
    private function simpanSoal(Materi $materi, array $soal): void
    {
        $kuis = $materi->kuis()->firstOrCreate();
        $kuis->soal()->delete();

        foreach (array_values($soal) as $i => $s) {
            $kuis->soal()->create([
                'urutan' => $i + 1,
                'pertanyaan' => $s['pertanyaan'],
                'pilihan' => array_values($s['pilihan']),
                'jawaban_benar' => (int) $s['jawaban_benar'],
                'pembahasan' => $s['pembahasan'] ?? null,
            ]);
        }
    }
}
