<?php

namespace App\Services;

use App\Models\Materi;
use App\Models\SoalKuis;

/**
 * Penilaian kuis pilihan ganda: skor = persentase jawaban benar (0-100).
 */
class KuisService
{
    /**
     * @param  array<int|string, int|string>  $jawaban  soal_id => indeks pilihan
     * @return array{skor: int, benar: int, total: int, rincian: list<array{soal_id: int, dipilih: int|null, jawaban_benar: int, benar: bool, pembahasan: string|null}>}
     */
    public function nilai(Materi $materi, array $jawaban): array
    {
        $soal = $materi->kuis->soal ?? collect();

        $rincian = array_values($soal->map(function (SoalKuis $s) use ($jawaban) {
            $dipilih = isset($jawaban[$s->id]) ? (int) $jawaban[$s->id] : null;

            return [
                'soal_id' => $s->id,
                'dipilih' => $dipilih,
                'jawaban_benar' => $s->jawaban_benar,
                'benar' => $dipilih === $s->jawaban_benar,
                'pembahasan' => $s->pembahasan,
            ];
        })->all());

        $total = count($rincian);
        $benar = count(array_filter($rincian, fn (array $r) => $r['benar']));

        return [
            'skor' => $total === 0 ? 0 : (int) round($benar / $total * 100),
            'benar' => $benar,
            'total' => $total,
            'rincian' => $rincian,
        ];
    }
}
