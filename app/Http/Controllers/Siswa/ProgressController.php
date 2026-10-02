<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\Logbook;
use App\Models\Materi;
use App\Services\PendampinganSiswaService;
use App\Services\ProgresKompetensiService;
use App\Support\KompetensiPresenter;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Progress siswa (bagian 6.8): overall, per kompetensi, gap selesai, logbook,
 * skor assessment, dan kompetensi terverifikasi industri.
 */
class ProgressController extends Controller
{
    public function __invoke(Request $request, PendampinganSiswaService $pendampingan, ProgresKompetensiService $progres): Response
    {
        $siswa = $request->user();
        $konteks = $pendampingan->konteks($siswa);
        $materi = $pendampingan->materiProgram($siswa);
        $status = $pendampingan->statusMateri($siswa, $materi);

        return Inertia::render('siswa/progress', [
            'ringkasan' => $progres->ringkasan($siswa),
            'kompetensi' => $pendampingan->peta($siswa)
                ->map(fn (array $b) => KompetensiPresenter::baris($b))
                ->values(),
            'skor' => $materi
                ->filter(fn (Materi $m) => $status[$m->id]['percobaan'] > 0)
                ->map(fn (Materi $m) => [
                    'materi_id' => $m->id,
                    'judul' => $m->judul,
                    'terbaik' => $status[$m->id]['terbaik'],
                    'nilai_minimal' => $m->nilai_minimal,
                    'lulus' => $status[$m->id]['lulus'],
                ])
                ->values(),
            'logbookPerMinggu' => $this->logbookPerMinggu(array_values($siswa->logbook()->get(['tanggal'])->map(fn (Logbook $l) => $l->tanggal)->all()), $konteks['periode_mulai']),
            'jumlahLogbook' => $siswa->logbook()->count(),
        ]);
    }

    /**
     * Jumlah logbook per minggu magang (minggu 1 dimulai pada periode_mulai).
     *
     * @param  list<CarbonImmutable>  $tanggal
     * @return list<array{minggu: string, jumlah: int}>
     */
    private function logbookPerMinggu(array $tanggal, ?string $mulai): array
    {
        if ($mulai === null) {
            return [];
        }

        $awal = Carbon::parse($mulai)->startOfDay();
        $sekarang = (int) floor($awal->diffInDays(now()->startOfDay()) / 7) + 1;
        $jumlah = array_fill(1, max(1, min($sekarang, 26)), 0);

        foreach ($tanggal as $t) {
            $minggu = (int) floor($awal->diffInDays(Carbon::parse($t)->startOfDay(), false) / 7) + 1;

            if (isset($jumlah[$minggu])) {
                $jumlah[$minggu]++;
            }
        }

        return array_map(
            fn (int $minggu, int $n) => ['minggu' => "M{$minggu}", 'jumlah' => $n],
            array_keys($jumlah),
            $jumlah,
        );
    }
}
