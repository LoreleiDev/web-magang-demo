<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Services\PendampinganSiswaService;
use App\Support\KompetensiPresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Peta Kompetensi (bagian 6.2) dan Learning Gap (bagian 6.3).
 */
class KompetensiController extends Controller
{
    public function peta(Request $request, PendampinganSiswaService $pendampingan): Response
    {
        return Inertia::render('siswa/peta-kompetensi', [
            'kompetensi' => $pendampingan->peta($request->user())
                ->map(fn (array $b) => KompetensiPresenter::baris($b))
                ->values(),
            'kompetensiUtamaId' => $request->user()->profilSiswa?->kompetensi_fokus_id,
        ]);
    }

    /**
     * "Kompetensi yang perlu Anda tingkatkan": gap > 0, urut gap terbesar.
     */
    public function gap(Request $request, PendampinganSiswaService $pendampingan): Response
    {
        $siswa = $request->user();
        $peta = $pendampingan->peta($siswa);
        $materi = $pendampingan->materiProgram($siswa);
        $status = $pendampingan->statusMateri($siswa, $materi);
        $rekomendasi = collect($pendampingan->rekomendasi($peta, $materi, $status, null, PHP_INT_MAX))
            ->keyBy('kompetensi_id');

        return Inertia::render('siswa/learning-gap', [
            'kompetensi' => $peta
                ->filter(fn (array $b) => $b['gap'] > 0)
                ->sortByDesc('gap')
                ->map(fn (array $b) => [
                    ...KompetensiPresenter::baris($b),
                    'materi_disarankan' => $rekomendasi->get($b['kompetensi']->id),
                    'jumlah_materi' => $materi->where('kompetensi_id', $b['kompetensi']->id)->count(),
                    'materi_pertama_id' => $materi->firstWhere('kompetensi_id', $b['kompetensi']->id)?->id,
                ])
                ->values(),
        ]);
    }
}
