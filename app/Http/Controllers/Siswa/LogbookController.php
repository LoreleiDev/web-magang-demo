<?php

namespace App\Http\Controllers\Siswa;

use App\Exceptions\AiMentorException;
use App\Http\Controllers\Controller;
use App\Models\Logbook;
use App\Services\AiMentorService;
use App\Support\LogbookPresenter;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Logbook harian siswa (bagian 6.6): satu per tanggal, bisa diedit, bukti
 * gambar/PDF maks. 5 MB (keputusan 13 no. 21). Tombol analisis AI di Tahap 6.
 */
class LogbookController extends Controller
{
    private const ISIAN = [
        'aktivitas', 'peralatan_software', 'sudah_dipahami', 'baru_ditemui',
        'kesulitan', 'pengetahuan_sekolah_digunakan', 'ingin_dipelajari',
    ];

    public function index(Request $request): Response
    {
        $siswa = $request->user();

        return Inertia::render('siswa/logbook', [
            'logbook' => $siswa->logbook()
                ->with('siswa.profilSiswa.kelompok:id,nama_kelompok')
                ->latest('tanggal')
                ->get()
                ->map(fn (Logbook $l) => [
                    ...LogbookPresenter::baris($l),
                    'nama_bukti' => $l->bukti_kegiatan ? basename($l->bukti_kegiatan) : null,
                    'hasil_analisis_ai' => $l->hasil_analisis_ai,
                ]),
            'hariIni' => now()->toDateString(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Logbook::class);

        $siswa = $request->user();
        $data = $this->validasi($request, null);

        $logbook = $siswa->logbook()->create([
            ...collect($data)->only(['tanggal', ...self::ISIAN])->all(),
        ]);
        $this->simpanBukti($request, $logbook);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Logbook tersimpan.']);

        return to_route('siswa.logbook.index');
    }

    public function update(Request $request, Logbook $logbook): RedirectResponse
    {
        Gate::authorize('update', $logbook);

        $data = $this->validasi($request, $logbook);

        $logbook->update(collect($data)->only(['tanggal', ...self::ISIAN])->all());

        if ($request->boolean('hapus_bukti') && $logbook->bukti_kegiatan) {
            Storage::disk('local')->delete($logbook->bukti_kegiatan);
            $logbook->update(['bukti_kegiatan' => null]);
        }

        $this->simpanBukti($request, $logbook);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Perubahan logbook tersimpan.']);

        return to_route('siswa.logbook.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function validasi(Request $request, ?Logbook $logbook): array
    {
        return $request->validate([
            'tanggal' => [
                'required', 'date', 'before_or_equal:today',
                // Satu logbook per tanggal; dibandingkan per tanggal agar tidak terpengaruh format jam.
                function (string $atribut, mixed $nilai, Closure $gagal) use ($request, $logbook) {
                    $ada = Logbook::query()
                        ->where('siswa_id', $request->user()->id)
                        ->whereDate('tanggal', (string) $nilai)
                        ->when($logbook, fn ($q) => $q->whereKeyNot($logbook->id))
                        ->exists();

                    if ($ada) {
                        $gagal('Logbook untuk tanggal ini sudah ada. Silakan edit logbook tersebut.');
                    }
                },
            ],
            'aktivitas' => ['required', 'string', 'max:3000'],
            'peralatan_software' => ['nullable', 'string', 'max:2000'],
            'sudah_dipahami' => ['nullable', 'string', 'max:2000'],
            'baru_ditemui' => ['nullable', 'string', 'max:2000'],
            'kesulitan' => ['nullable', 'string', 'max:2000'],
            'pengetahuan_sekolah_digunakan' => ['nullable', 'string', 'max:2000'],
            'ingin_dipelajari' => ['nullable', 'string', 'max:2000'],
            'bukti_kegiatan' => ['nullable', 'file', 'extensions:jpg,jpeg,png,webp,pdf', 'max:5120'],
            'hapus_bukti' => ['boolean'],
        ], [
            'tanggal.before_or_equal' => 'Tanggal logbook tidak boleh lebih dari hari ini.',
            'aktivitas.required' => 'Ceritakan aktivitas Anda hari ini.',
            'bukti_kegiatan.extensions' => 'Bukti kegiatan harus berupa gambar (JPG/PNG/WEBP) atau PDF.',
            'bukti_kegiatan.max' => 'Ukuran bukti kegiatan maksimal 5 MB.',
        ]);
    }

    /**
     * Tombol "Analisis dengan AI" (bagian 6.6 & 9.5). Hasil JSON divalidasi lalu
     * disimpan di `hasil_analisis_ai`; kegagalan ditampilkan sebagai pesan ramah.
     */
    public function analisis(Logbook $logbook, AiMentorService $ai): RedirectResponse
    {
        Gate::authorize('update', $logbook);
        Gate::authorize('gunakan-ai-mentor');

        try {
            $logbook->update(['hasil_analisis_ai' => $ai->analisisLogbook($logbook)]);
        } catch (AiMentorException $e) {
            return back()->withErrors(['analisis' => $e->getMessage()]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Analisis AI selesai.']);

        return back();
    }

    private function simpanBukti(Request $request, Logbook $logbook): void
    {
        $file = $request->file('bukti_kegiatan');

        if (! $file instanceof UploadedFile) {
            return;
        }

        if ($logbook->bukti_kegiatan) {
            Storage::disk('local')->delete($logbook->bukti_kegiatan);
        }

        $logbook->update([
            'bukti_kegiatan' => $file->storeAs(
                "logbook/{$logbook->siswa_id}",
                $logbook->tanggal->toDateString().'-'.$file->hashName(),
                'local',
            ),
        ]);
    }
}
