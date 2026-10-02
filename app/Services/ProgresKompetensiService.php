<?php

namespace App\Services;

use App\Enums\StatusKompetensi;
use App\Models\HasilAssessment;
use App\Models\Kompetensi;
use App\Models\Materi;
use App\Models\ProgresKompetensi;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Perhitungan level, gap, progres, dan perpindahan status kompetensi siswa.
 *
 * - Level naik otomatis dari kuis materi berlevel; awalnya 1 (bagian 11.1, keputusan 13 no. 24).
 * - gap = target_level - level; merah >= 2, kuning = 1, hijau <= 0 (bagian 6.2).
 * - Persentase progres = kompetensi dengan level >= target (keputusan 13 no. 19).
 * - Status berpindah otomatis (bagian 10).
 *
 * @phpstan-type BarisPeta array{kompetensi: Kompetensi, progres: ProgresKompetensi|null, level: int, level_diisi: bool, gap: int, warna: string, status: StatusKompetensi}
 */
class ProgresKompetensiService
{
    public const LEVEL_BAWAAN = 1;

    /** @var array<string, Collection<int, Kompetensi>> */
    private array $kompetensiPerProgram = [];

    public static function warnaGap(int $gap): string
    {
        return match (true) {
            $gap >= 2 => 'tinggi',
            $gap === 1 => 'sedang',
            default => 'aman',
        };
    }

    /**
     * Peta kompetensi siswa: semua kompetensi program keahliannya beserta progresnya.
     *
     * @return Collection<int, BarisPeta>
     */
    public function peta(User $siswa): Collection
    {
        $program = $siswa->programKeahlian();

        if ($program === null) {
            return collect();
        }

        $progres = $siswa->progresKompetensi()->get()->keyBy('kompetensi_id');

        return $this->kompetensiProgram($program)->map(function (Kompetensi $k) use ($progres) {
            /** @var ProgresKompetensi|null $p */
            $p = $progres->get($k->id);
            $level = $p->level_siswa ?? self::LEVEL_BAWAAN;
            $gap = $k->target_level - $level;

            return [
                'kompetensi' => $k,
                'progres' => $p,
                'level' => $level,
                'level_diisi' => $p?->level_siswa !== null,
                'gap' => $gap,
                'warna' => self::warnaGap($gap),
                'status' => $p->status ?? StatusKompetensi::BelumDipelajari,
            ];
        })->values();
    }

    /**
     * @return array{total: int, dikuasai: int, gap: int, terverifikasi: int, persen: int}
     */
    public function ringkasan(User $siswa): array
    {
        $peta = $this->peta($siswa);
        $total = $peta->count();
        $dikuasai = $peta->where('gap', '<=', 0)->count();

        return [
            'total' => $total,
            'dikuasai' => $dikuasai,
            'gap' => $total - $dikuasai,
            'terverifikasi' => $peta->where('status', StatusKompetensi::Terverifikasi)->count(),
            'persen' => $total === 0 ? 0 : (int) round($dikuasai / $total * 100),
        ];
    }

    /**
     * Kompetensi yang paling dikuasai siswa (keputusan 13 no. 30): level tertinggi,
     * lalu yang sudah terverifikasi, lalu yang naik level paling akhir. Null jika
     * semua kompetensi masih level awal.
     *
     * @return array{nama: string, level: int, terverifikasi: bool}|null
     */
    public function palingDikuasai(User $siswa): ?array
    {
        $terbaik = $this->peta($siswa)
            ->filter(fn (array $b) => $b['level'] > self::LEVEL_BAWAAN)
            ->sortBy([
                fn (array $a, array $b) => $b['level'] <=> $a['level'],
                fn (array $a, array $b) => ($b['status'] === StatusKompetensi::Terverifikasi) <=> ($a['status'] === StatusKompetensi::Terverifikasi),
                fn (array $a, array $b) => ($b['progres']?->tanggal_level_diisi?->getTimestamp() ?? 0) <=> ($a['progres']?->tanggal_level_diisi?->getTimestamp() ?? 0),
            ])
            ->first();

        return $terbaik === null ? null : [
            'nama' => $terbaik['kompetensi']->nama_kompetensi_sekolah,
            'level' => $terbaik['level'],
            'terverifikasi' => $terbaik['status'] === StatusKompetensi::Terverifikasi,
        ];
    }

    /**
     * Siswa membuka materi -> "Sedang dipelajari" (jika belum lebih jauh).
     */
    public function tandaiMulaiBelajar(User $siswa, Kompetensi $kompetensi): ProgresKompetensi
    {
        $progres = $this->progres($siswa, $kompetensi);
        $progres->status = $this->naikkan($progres->status, StatusKompetensi::SedangDipelajari);
        $progres->save();

        return $progres;
    }

    /**
     * Simpan satu percobaan kuis. Jika lulus (skor >= nilai minimal materi):
     * level siswa = level materi bila lebih tinggi (tidak pernah turun), status
     * minimal "Sedang dipraktikkan", dan "Menunggu verifikasi" bila level >= target.
     */
    public function catatHasilKuis(User $siswa, Materi $materi, int $skor): HasilAssessment
    {
        $materi->loadMissing(['kompetensi', 'kuis']);
        $lulus = $skor >= $materi->nilai_minimal;

        $hasil = HasilAssessment::create([
            'siswa_id' => $siswa->id,
            'kuis_id' => $materi->kuis?->id,
            'skor' => $skor,
            'lulus' => $lulus,
            'tanggal' => now(),
        ]);

        $kompetensi = $materi->kompetensi;
        $progres = $this->progres($siswa, $kompetensi);
        $status = $this->naikkan($progres->status, StatusKompetensi::SedangDipelajari);

        if ($lulus) {
            $levelLama = $progres->level_siswa ?? self::LEVEL_BAWAAN;

            if ($materi->level > $levelLama) {
                $progres->level_siswa = $materi->level;
                $progres->tanggal_level_diisi = now();
            }

            $status = $this->naikkan($status, StatusKompetensi::SedangDipraktikkan);

            if (($progres->level_siswa ?? self::LEVEL_BAWAAN) >= $kompetensi->target_level) {
                $status = $this->naikkan($status, StatusKompetensi::MenungguVerifikasi);
            }
        }

        $progres->status = $status;
        $progres->save();

        return $hasil;
    }

    private function progres(User $siswa, Kompetensi $kompetensi): ProgresKompetensi
    {
        $progres = ProgresKompetensi::firstOrNew([
            'siswa_id' => $siswa->id,
            'kompetensi_id' => $kompetensi->id,
        ]);
        $progres->status ??= StatusKompetensi::BelumDipelajari;

        return $progres;
    }

    /**
     * Status tidak pernah mundur (bagian 10).
     */
    private function naikkan(StatusKompetensi $sekarang, StatusKompetensi $tujuan): StatusKompetensi
    {
        return $tujuan->urutan() > $sekarang->urutan() ? $tujuan : $sekarang;
    }

    /**
     * @return Collection<int, Kompetensi>
     */
    private function kompetensiProgram(string $program): Collection
    {
        return $this->kompetensiPerProgram[$program] ??= Kompetensi::query()
            ->where('program_keahlian', $program)
            ->orderBy('id')
            ->get();
    }
}
