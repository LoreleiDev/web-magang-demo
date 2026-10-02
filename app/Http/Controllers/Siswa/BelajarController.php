<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\Materi;
use App\Models\SoalKuis;
use App\Services\KuisService;
use App\Services\PendampinganSiswaService;
use App\Services\ProgresKompetensiService;
use App\Support\MateriPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Belajar / microlearning 8 langkah + kuis (bagian 6.4 & 6.7).
 */
class BelajarController extends Controller
{
    public function index(Request $request, PendampinganSiswaService $pendampingan): Response
    {
        $siswa = $request->user();
        $materi = $pendampingan->materiProgram($siswa);
        $status = $pendampingan->statusMateri($siswa, $materi);
        $peta = $pendampingan->peta($siswa)->keyBy(fn (array $b) => $b['kompetensi']->id);

        return Inertia::render('siswa/belajar/index', [
            'kompetensi' => $materi->groupBy('kompetensi_id')->map(function ($daftar, $kompetensiId) use ($peta, $status) {
                $baris = $peta->get($kompetensiId);

                return [
                    'kompetensi_id' => (int) $kompetensiId,
                    'nama' => $daftar->first()->kompetensi->nama_kompetensi_sekolah,
                    'level' => $baris['level'] ?? 1,
                    'target_level' => $daftar->first()->kompetensi->target_level,
                    'warna' => $baris['warna'] ?? 'aman',
                    'materi' => $daftar->map(fn (Materi $m) => [
                        ...MateriPresenter::ringkas($m),
                        'status_kuis' => $status[$m->id],
                    ])->values(),
                ];
            })->values(),
            'fokusId' => $siswa->profilSiswa?->kompetensi_fokus_id,
        ]);
    }

    /**
     * Membuka materi menandai kompetensinya "Sedang dipelajari" (bagian 10).
     */
    public function show(Request $request, Materi $materi, PendampinganSiswaService $pendampingan, ProgresKompetensiService $progres): Response
    {
        Gate::authorize('view', $materi);

        $siswa = $request->user();
        $progres->tandaiMulaiBelajar($siswa, $materi->kompetensi);

        $peta = $progres->peta($siswa)->first(fn (array $b) => $b['kompetensi']->id === $materi->kompetensi_id);
        $lain = $pendampingan->materiProgram($siswa)->where('kompetensi_id', $materi->kompetensi_id)->where('id', '!=', $materi->id);

        return Inertia::render('siswa/belajar/show', [
            // Kunci jawaban tidak dikirim ke siswa sebelum ia menjawab.
            'materi' => MateriPresenter::detail($materi, denganKunci: false),
            'statusKuis' => $pendampingan->statusMateri($siswa, collect([$materi->loadMissing('kuis')]))[$materi->id],
            'levelSiswa' => $peta['level'] ?? 1,
            'targetLevel' => $materi->kompetensi->target_level,
            'materiLain' => $lain->map(fn (Materi $m) => MateriPresenter::ringkas($m))->values(),
        ]);
    }

    /**
     * Kirim jawaban kuis: simpan percobaan, naikkan level jika lulus (bagian 11.1),
     * dan kembalikan pembahasan lewat flash.
     */
    public function kuis(Request $request, Materi $materi, KuisService $kuis, ProgresKompetensiService $progres): RedirectResponse
    {
        Gate::authorize('view', $materi);

        $materi->load(['kuis.soal', 'kompetensi']);
        abort_if($materi->kuis === null || $materi->kuis->soal->isEmpty(), 404);

        $aturan = ['jawaban' => ['required', 'array']];
        foreach ($materi->kuis->soal as $s) {
            /** @var SoalKuis $s */
            $aturan["jawaban.{$s->id}"] = ['required', 'integer', 'min:0', 'max:'.(count($s->pilihan) - 1)];
        }

        $request->validate($aturan, [
            'jawaban.required' => 'Jawab semua soal terlebih dahulu.',
            'jawaban.*.required' => 'Soal ini belum dijawab.',
        ]);

        $siswa = $request->user();
        $levelSebelum = $siswa->progresKompetensi()->where('kompetensi_id', $materi->kompetensi_id)->value('level_siswa') ?? ProgresKompetensiService::LEVEL_BAWAAN;

        $nilai = $kuis->nilai($materi, (array) $request->input('jawaban'));
        $hasil = $progres->catatHasilKuis($siswa, $materi, $nilai['skor']);

        $levelSesudah = $siswa->progresKompetensi()->where('kompetensi_id', $materi->kompetensi_id)->value('level_siswa') ?? ProgresKompetensiService::LEVEL_BAWAAN;

        Inertia::flash('hasilKuis', [
            'materi_id' => $materi->id,
            'skor' => $nilai['skor'],
            'benar' => $nilai['benar'],
            'total' => $nilai['total'],
            'lulus' => $hasil->lulus,
            'nilai_minimal' => $materi->nilai_minimal,
            'level_naik' => $levelSesudah > $levelSebelum ? $levelSesudah : null,
            'rincian' => $nilai['rincian'],
        ]);

        return back();
    }
}
