<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\HasilAssessment;
use App\Models\Materi;
use App\Services\PendampinganSiswaService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Assessment siswa (bagian 6.7): skor per kuis, dan rekomendasi penguatan bila
 * skor terbaik di bawah nilai minimal materi.
 */
class AssessmentController extends Controller
{
    public function __invoke(Request $request, PendampinganSiswaService $pendampingan): Response
    {
        $siswa = $request->user();
        $materi = $pendampingan->materiProgram($siswa);
        $status = $pendampingan->statusMateri($siswa, $materi);

        return Inertia::render('siswa/assessment', [
            'kuis' => $materi->map(fn (Materi $m) => [
                'materi_id' => $m->id,
                'judul' => $m->judul,
                'kompetensi' => $m->kompetensi->nama_kompetensi_sekolah,
                'level' => $m->level,
                'nilai_minimal' => $m->nilai_minimal,
                ...$status[$m->id],
                // Materi lain di kompetensi yang sama sebagai bahan penguatan.
                'penguatan' => $materi
                    ->where('kompetensi_id', $m->kompetensi_id)
                    ->where('id', '!=', $m->id)
                    ->take(2)
                    ->map(fn (Materi $x) => ['id' => $x->id, 'judul' => $x->judul])
                    ->values(),
            ])->values(),
            'riwayat' => $siswa->hasilAssessment()
                ->with('kuis.materi:id,judul,nilai_minimal')
                ->latest('tanggal')
                ->limit(30)
                ->get()
                ->map(fn (HasilAssessment $h) => [
                    'id' => $h->id,
                    'materi_id' => $h->kuis->materi->id,
                    'materi' => $h->kuis->materi->judul,
                    'skor' => $h->skor,
                    'lulus' => $h->lulus,
                    'tanggal' => $h->tanggal->toIso8601String(),
                ]),
        ]);
    }
}
