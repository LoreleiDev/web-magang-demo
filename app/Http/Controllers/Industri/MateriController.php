<?php

namespace App\Http\Controllers\Industri;

use App\Enums\HasilVerifikasiMateri;
use App\Http\Controllers\Controller;
use App\Models\Materi;
use App\Models\VerifikasiMateri;
use App\Services\VerifikasiMateriService;
use App\Support\MateriPresenter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Verifikasi Materi (bagian 8 & 11.2): materi untuk program keahlian siswa yang
 * magang di perusahaan industri ini.
 */
class MateriController extends Controller
{
    public function index(Request $request): Response
    {
        $industri = $request->user();
        $perusahaanId = $industri->perusahaanId();

        $materi = Materi::query()
            ->whereHas('kompetensi', fn (Builder $q) => $q->whereIn('program_keahlian', $industri->programKeahlianSiswaPerusahaan()))
            ->with(['kompetensi.program', 'pembuat:id,name'])
            ->with(['verifikasi' => fn ($q) => $q->where('perusahaan_id', $perusahaanId)->latest()])
            ->latest('diubah_terakhir')
            ->get()
            ->map(function (Materi $m) {
                /** @var VerifikasiMateri|null $terakhir */
                $terakhir = $m->verifikasi->first();
                $masihBerlaku = $terakhir && ($m->diubah_terakhir === null || $terakhir->created_at >= $m->diubah_terakhir);

                return [
                    ...MateriPresenter::ringkas($m),
                    // Hasil pemeriksaan perusahaan ini; dianggap kedaluwarsa jika materi diedit sesudahnya.
                    'pemeriksaan_saya' => $terakhir ? [
                        'hasil' => $terakhir->hasil->value,
                        'hasil_label' => $terakhir->hasil->label(),
                        'tanggal' => $terakhir->created_at?->toIso8601String(),
                        'perlu_ulang' => ! $masihBerlaku,
                    ] : null,
                ];
            });

        return Inertia::render('industri/materi/index', [
            'materi' => $materi,
        ]);
    }

    public function show(Materi $materi): Response
    {
        Gate::authorize('verifikasi', $materi);

        return Inertia::render('industri/materi/show', [
            'materi' => MateriPresenter::detail($materi, denganKunci: true),
            'riwayatVerifikasi' => MateriPresenter::riwayatVerifikasi($materi),
        ]);
    }

    /**
     * "Verifikasi Materi" (masukan opsional) atau "Belum Sesuai" (masukan wajib).
     */
    public function periksa(Request $request, Materi $materi, VerifikasiMateriService $layanan): RedirectResponse
    {
        Gate::authorize('verifikasi', $materi);

        $data = $request->validate([
            'hasil' => ['required', Rule::enum(HasilVerifikasiMateri::class)],
            'masukan' => [
                Rule::requiredIf($request->input('hasil') === HasilVerifikasiMateri::BelumSesuai->value),
                'nullable', 'string', 'max:3000',
            ],
        ], [
            'hasil.required' => 'Pilih hasil pemeriksaan.',
            'masukan.required' => 'Tuliskan masukan untuk guru jika materi belum sesuai.',
        ]);

        $hasil = HasilVerifikasiMateri::from($data['hasil']);
        $layanan->periksa($materi, $request->user(), $hasil, $data['masukan'] ?? null);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $hasil === HasilVerifikasiMateri::Diverifikasi
                ? 'Materi diverifikasi. Guru pembuat materi akan menerima email.'
                : 'Masukan terkirim ke guru pembuat materi.',
        ]);

        return to_route('industri.materi.index');
    }
}
