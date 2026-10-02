<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\Logbook;
use App\Services\PendampinganSiswaService;
use App\Services\ProgresKompetensiService;
use App\Support\KompetensiPresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Dashboard siswa (bagian 6.1).
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request, PendampinganSiswaService $pendampingan, ProgresKompetensiService $progres): Response
    {
        $siswa = $request->user();
        $konteks = $pendampingan->konteks($siswa);
        $peta = $pendampingan->peta($siswa);
        $materi = $pendampingan->materiProgram($siswa);
        $status = $pendampingan->statusMateri($siswa, $materi);
        $rekomendasi = $pendampingan->rekomendasi($peta, $materi, $status, $konteks['kompetensi_fokus_id']);

        /** @var Logbook|null $logbook */
        $logbook = $siswa->logbook()->latest('tanggal')->first();

        $fokus = $peta->first(fn (array $b) => $b['kompetensi']->id === $konteks['kompetensi_fokus_id']);

        return Inertia::render('siswa/dashboard', [
            'konteks' => $konteks,
            'ringkasan' => $progres->ringkasan($siswa),
            'fokus' => $fokus ? KompetensiPresenter::baris($fokus) : null,
            'kompetensiGap' => $peta->filter(fn (array $b) => $b['gap'] > 0)
                ->sortByDesc('gap')
                ->take(4)
                ->map(fn (array $b) => KompetensiPresenter::baris($b))
                ->values(),
            'aktivitasHariIni' => $pendampingan->aktivitasHariIni($siswa, $rekomendasi, $materi, $status),
            'rekomendasi' => $rekomendasi,
            'logbookTerakhir' => $logbook ? [
                'id' => $logbook->id,
                'tanggal' => $logbook->tanggal->toDateString(),
                'aktivitas' => $logbook->aktivitas,
                'kesulitan' => $logbook->kesulitan,
            ] : null,
        ]);
    }
}
