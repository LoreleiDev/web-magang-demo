<?php

namespace App\Services;

use App\Enums\StatusKompetensi;
use App\Models\Kompetensi;
use App\Models\ProgresKompetensi;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Perhitungan level, gap, progres, dan perpindahan status kompetensi siswa.
 *
 * - Level yang belum diisi guru dianggap 1 (keputusan 13 no. 24).
 * - gap = target_level - level; merah >= 2, kuning = 1, hijau <= 0 (bagian 6.2).
 * - Persentase progres = kompetensi dengan level >= target (keputusan 13 no. 19).
 * - Status berpindah otomatis (bagian 10).
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
     * @return Collection<int, array{kompetensi: Kompetensi, progres: ProgresKompetensi|null, level: int, level_diisi: bool, gap: int, warna: string, status: StatusKompetensi}>
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
     * Guru mengisi/mengubah level siswa (bagian 11.1) dan status ikut berpindah.
     */
    public function isiLevel(User $siswa, Kompetensi $kompetensi, int $level, User $guru): ProgresKompetensi
    {
        $progres = ProgresKompetensi::firstOrNew([
            'siswa_id' => $siswa->id,
            'kompetensi_id' => $kompetensi->id,
        ]);

        $progres->fill([
            'level_siswa' => $level,
            'level_diisi_oleh' => $guru->id,
            'tanggal_level_diisi' => now(),
        ]);

        $progres->status = $this->statusSetelahIsiLevel($progres->status ?? StatusKompetensi::BelumDipelajari, $level, $kompetensi->target_level);
        $progres->save();

        return $progres;
    }

    /**
     * Level >= target -> "Menunggu verifikasi". Jika level diturunkan di bawah target
     * sebelum diverifikasi -> kembali "Sedang dipraktikkan" (aturan sementara, bagian 10).
     * Status "Terverifikasi" tidak diubah oleh guru.
     */
    private function statusSetelahIsiLevel(StatusKompetensi $status, int $level, int $target): StatusKompetensi
    {
        return match (true) {
            $status === StatusKompetensi::Terverifikasi => $status,
            $level >= $target => StatusKompetensi::MenungguVerifikasi,
            $status === StatusKompetensi::MenungguVerifikasi => StatusKompetensi::SedangDipraktikkan,
            default => $status,
        };
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
