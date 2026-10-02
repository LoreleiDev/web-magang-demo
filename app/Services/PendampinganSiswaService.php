<?php

namespace App\Services;

use App\Models\Materi;
use App\Models\ProgramKeahlian;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Data halaman siswa: konteks magang, status kuis per materi, rekomendasi materi,
 * dan aktivitas hari ini (CLAUDE.md bagian 6, keputusan 13 no. 20).
 *
 * @phpstan-import-type BarisPeta from ProgresKompetensiService
 */
class PendampinganSiswaService
{
    public function __construct(private ProgresKompetensiService $progres) {}

    /**
     * @return array<string, mixed>
     */
    public function konteks(User $siswa): array
    {
        $siswa->loadMissing('profilSiswa.kelompok.perusahaan');
        $profil = $siswa->profilSiswa;
        $kelompok = $profil?->kelompok;
        $hariIni = now()->startOfDay();

        $status = match (true) {
            $kelompok === null => null,
            $hariIni->lt($kelompok->periode_mulai) => 'belum_mulai',
            $hariIni->gt($kelompok->periode_selesai) => 'selesai',
            default => 'berjalan',
        };

        return [
            'nama' => $siswa->name,
            'id_siswa' => $profil?->id_siswa,
            'program_keahlian' => $profil?->program_keahlian,
            'program_keahlian_nama' => ProgramKeahlian::nama($profil?->program_keahlian),
            'perusahaan' => $kelompok?->perusahaan->nama,
            'unit_kerja' => $profil?->unit_kerja,
            'kelompok' => $kelompok?->nama_kelompok,
            'periode_mulai' => $kelompok?->periode_mulai->toDateString(),
            'periode_selesai' => $kelompok?->periode_selesai->toDateString(),
            'status_magang' => $status,
            // Hari pertama magang = hari ke-1 (bagian 6.1).
            'hari_ke' => $status === 'berjalan' ? (int) $kelompok->periode_mulai->diffInDays($hariIni) + 1 : null,
            'total_hari' => $kelompok ? (int) $kelompok->periode_mulai->diffInDays($kelompok->periode_selesai) + 1 : null,
            'hari_menuju_mulai' => $status === 'belum_mulai' ? (int) $hariIni->diffInDays($kelompok->periode_mulai) : null,
            'kompetensi_fokus_id' => $profil?->kompetensi_fokus_id,
        ];
    }

    /**
     * Materi untuk program keahlian siswa, urut kompetensi lalu level.
     *
     * @return Collection<int, Materi>
     */
    public function materiProgram(User $siswa): Collection
    {
        $program = $siswa->programKeahlian();

        return Materi::query()
            ->whereHas('kompetensi', fn (Builder $q) => $q->where('program_keahlian', $program))
            ->with(['kompetensi.program', 'pembuat:id,name', 'kuis:id,materi_id'])
            ->orderBy('kompetensi_id')
            ->orderBy('level')
            ->orderBy('id')
            ->get();
    }

    /**
     * Status kuis per materi: skor terbaik, jumlah percobaan, dan lulus/tidak
     * (skor terbaik >= nilai minimal materi; keputusan 13 no. 22 & 29).
     *
     * @param  Collection<int, Materi>  $materi
     * @return array<int, array{terbaik: int|null, percobaan: int, lulus: bool, terakhir: string|null}>
     */
    public function statusMateri(User $siswa, Collection $materi): array
    {
        $hasil = $siswa->hasilAssessment()
            ->selectRaw('kuis_id, max(skor) as terbaik, count(*) as percobaan, max(tanggal) as terakhir')
            ->groupBy('kuis_id')
            ->get()
            ->keyBy('kuis_id');

        return $materi->mapWithKeys(function (Materi $m) use ($hasil) {
            $baris = $m->kuis ? $hasil->get($m->kuis->id) : null;
            $terbaik = $baris ? (int) $baris->getAttribute('terbaik') : null;

            return [$m->id => [
                'terbaik' => $terbaik,
                'percobaan' => $baris ? (int) $baris->getAttribute('percobaan') : 0,
                'lulus' => $terbaik !== null && $terbaik >= $m->nilai_minimal,
                'terakhir' => $baris ? (string) $baris->getAttribute('terakhir') : null,
            ]];
        })->all();
    }

    /**
     * Materi yang disarankan: untuk kompetensi yang masih gap (fokus dulu, lalu gap
     * terbesar), ambil materi belum lulus dengan level terendah di atas level siswa.
     *
     * @param  Collection<int, BarisPeta>  $peta
     * @param  Collection<int, Materi>  $materi
     * @param  array<int, array{terbaik: int|null, percobaan: int, lulus: bool, terakhir: string|null}>  $status
     * @return list<array<string, mixed>>
     */
    public function rekomendasi(Collection $peta, Collection $materi, array $status, ?int $fokusId, int $batas = 3): array
    {
        return array_values($peta
            ->filter(fn (array $b) => $b['gap'] > 0)
            ->sortBy([
                fn (array $a, array $b) => ($b['kompetensi']->id === $fokusId) <=> ($a['kompetensi']->id === $fokusId),
                fn (array $a, array $b) => $b['gap'] <=> $a['gap'],
            ])
            ->map(function (array $b) use ($materi, $status) {
                $pilihan = $materi
                    ->where('kompetensi_id', $b['kompetensi']->id)
                    ->reject(fn (Materi $m) => $status[$m->id]['lulus'] ?? false)
                    ->sortBy(fn (Materi $m) => [$m->level <= $b['level'] ? 1 : 0, $m->level])
                    ->first();

                return $pilihan ? [
                    'materi_id' => $pilihan->id,
                    'judul' => $pilihan->judul,
                    'level' => $pilihan->level,
                    'terverifikasi_industri' => $pilihan->terverifikasi_industri,
                    'kompetensi_id' => $b['kompetensi']->id,
                    'kompetensi' => $b['kompetensi']->nama_kompetensi_sekolah,
                    'gap' => $b['gap'],
                    'warna' => $b['warna'],
                ] : null;
            })
            ->filter()
            ->take($batas)
            ->all());
    }

    /**
     * "Aktivitas yang harus dilakukan hari ini" (keputusan 13 no. 20).
     *
     * @param  list<array<string, mixed>>  $rekomendasi
     * @param  Collection<int, Materi>  $materi
     * @param  array<int, array{terbaik: int|null, percobaan: int, lulus: bool, terakhir: string|null}>  $status
     * @return list<array<string, mixed>>
     */
    public function aktivitasHariIni(User $siswa, array $rekomendasi, Collection $materi, array $status): array
    {
        $aktivitas = [];

        if (! $siswa->logbook()->whereDate('tanggal', now()->toDateString())->exists()) {
            $aktivitas[] = [
                'jenis' => 'logbook',
                'judul' => 'Isi logbook hari ini',
                'keterangan' => 'Catat aktivitas, kesulitan, dan hal baru yang Anda temui.',
            ];
        }

        if ($rekomendasi !== []) {
            $r = $rekomendasi[0];
            $aktivitas[] = [
                'jenis' => 'belajar',
                'judul' => "Pelajari \"{$r['judul']}\"",
                'keterangan' => "{$r['kompetensi']} · gap {$r['gap']}",
                'materi_id' => $r['materi_id'],
            ];
        }

        foreach ($materi as $m) {
            $s = $status[$m->id] ?? null;

            if ($s !== null && $s['percobaan'] > 0 && ! $s['lulus']) {
                $aktivitas[] = [
                    'jenis' => 'kuis',
                    'judul' => "Ulangi kuis \"{$m->judul}\"",
                    'keterangan' => "Skor terbaik {$s['terbaik']}, minimal {$m->nilai_minimal}.",
                    'materi_id' => $m->id,
                ];
            }
        }

        return $aktivitas;
    }

    /**
     * Peta kompetensi siswa (diteruskan dari ProgresKompetensiService).
     *
     * @return Collection<int, BarisPeta>
     */
    public function peta(User $siswa): Collection
    {
        return $this->progres->peta($siswa);
    }
}
