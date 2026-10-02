<?php

namespace App\Services;

use App\Models\HasilAssessment;
use App\Models\Logbook;
use App\Models\User;
use App\Support\KompetensiPresenter;
use App\Support\LogbookPresenter;
use Illuminate\Support\Collection;

/**
 * Ringkasan & detail pemantauan siswa untuk guru pembimbing dan pembimbing industri
 * (CLAUDE.md bagian 7 & 8).
 */
class RingkasanSiswaService
{
    public function __construct(private ProgresKompetensiService $progres) {}

    /**
     * Satu baris di daftar siswa: progress, learning gap, aktivitas terakhir, logbook,
     * hasil assessment, dan status verifikasi.
     *
     * @return array<string, mixed>
     */
    public function baris(User $siswa): array
    {
        $siswa->loadMissing('profilSiswa.kompetensiFokus');

        $logbookTerakhir = $siswa->logbook()->latest('tanggal')->first();
        $hasilTerakhir = $siswa->hasilAssessment()->with('kuis.materi:id,judul')->latest('tanggal')->first();

        return [
            'id' => $siswa->id,
            'nama' => $siswa->name,
            'id_siswa' => $siswa->profilSiswa?->id_siswa,
            'program_keahlian' => $siswa->profilSiswa?->program_keahlian,
            'unit_kerja' => $siswa->profilSiswa?->unit_kerja,
            'status_aktif' => $siswa->status_aktif,
            'progres' => $this->progres->ringkasan($siswa),
            'kompetensi_utama' => $siswa->profilSiswa?->kompetensiFokus?->nama_kompetensi_sekolah,
            'paling_dikuasai' => $this->progres->palingDikuasai($siswa),
            'jumlah_logbook' => $siswa->logbook()->count(),
            'logbook_terakhir' => $logbookTerakhir?->tanggal->toDateString(),
            'rata_skor' => $this->rataSkorTerbaik($siswa),
            'aktivitas_terakhir' => $this->aktivitasTerakhir($logbookTerakhir, $hasilTerakhir),
        ];
    }

    /**
     * Detail siswa: peta kompetensi, logbook, dan semua hasil assessment.
     *
     * @return array<string, mixed>
     */
    public function detail(User $siswa): array
    {
        $siswa->loadMissing('profilSiswa.kelompok.perusahaan');
        $kelompok = $siswa->profilSiswa?->kelompok;

        return [
            'siswa' => [
                ...$this->baris($siswa),
                'email' => $siswa->email,
                'kelompok' => $kelompok?->nama_kelompok,
                'perusahaan' => $kelompok?->perusahaan->nama,
                'periode_mulai' => $kelompok?->periode_mulai->toDateString(),
                'periode_selesai' => $kelompok?->periode_selesai->toDateString(),
            ],
            'kompetensi' => $this->progres->peta($siswa)
                ->each(fn (array $b) => $b['progres']?->loadMissing('verifikator:id,name'))
                ->map(fn (array $b) => KompetensiPresenter::baris($b))
                ->values(),
            'logbook' => $siswa->logbook()
                ->with('siswa.profilSiswa.kelompok:id,nama_kelompok')
                ->latest('tanggal')
                ->limit(30)
                ->get()
                ->map(fn (Logbook $l) => LogbookPresenter::baris($l)),
            'assessment' => $siswa->hasilAssessment()
                ->with('kuis.materi:id,judul')
                ->latest('tanggal')
                ->get()
                ->map(fn (HasilAssessment $h) => [
                    'id' => $h->id,
                    'materi' => $h->kuis->materi->judul,
                    'skor' => $h->skor,
                    'lulus' => $h->lulus,
                    'tanggal' => $h->tanggal->toIso8601String(),
                ]),
        ];
    }

    /**
     * Rata-rata skor terbaik per kuis (keputusan 13 no. 22), null jika belum pernah kuis.
     */
    private function rataSkorTerbaik(User $siswa): ?int
    {
        /** @var Collection<int, int> $terbaik */
        $terbaik = $siswa->hasilAssessment()
            ->selectRaw('kuis_id, max(skor) as terbaik')
            ->groupBy('kuis_id')
            ->pluck('terbaik')
            ->map(fn ($s) => (int) $s);

        return $terbaik->isEmpty() ? null : (int) round($terbaik->avg());
    }

    /**
     * @return array{jenis: string, keterangan: string, tanggal: string}|null
     */
    private function aktivitasTerakhir(?Logbook $logbook, ?HasilAssessment $hasil): ?array
    {
        $dariLogbook = $logbook ? [
            'jenis' => 'logbook',
            'keterangan' => 'Mengisi logbook',
            'tanggal' => ($logbook->updated_at ?? $logbook->tanggal)->toIso8601String(),
        ] : null;

        $dariKuis = $hasil ? [
            'jenis' => 'kuis',
            'keterangan' => "Mengerjakan kuis \"{$hasil->kuis->materi->judul}\" (skor {$hasil->skor})",
            'tanggal' => $hasil->tanggal->toIso8601String(),
        ] : null;

        if ($dariLogbook === null || $dariKuis === null) {
            return $dariLogbook ?? $dariKuis;
        }

        return $dariLogbook['tanggal'] >= $dariKuis['tanggal'] ? $dariLogbook : $dariKuis;
    }
}
