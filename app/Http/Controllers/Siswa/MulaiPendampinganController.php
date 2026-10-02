<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsurePendampinganDimulai;
use App\Services\PendampinganSiswaService;
use App\Support\KompetensiPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * Halaman awal siswa (bagian 4.1): data terkunci dari akun & kelompok, pilih unit
 * kerja dan kompetensi utama. Hanya muncul sampai keduanya terisi (keputusan 13
 * no. 18); setelah itu diubah lewat Dashboard / Peta Kompetensi.
 */
class MulaiPendampinganController extends Controller
{
    public function show(Request $request, PendampinganSiswaService $pendampingan): Response|HttpResponse
    {
        $siswa = $request->user();

        if (EnsurePendampinganDimulai::selesai($siswa->profilSiswa)) {
            return to_route('siswa.dashboard');
        }

        $kelompok = $siswa->profilSiswa?->kelompok;

        return Inertia::render('siswa/mulai', [
            'konteks' => $pendampingan->konteks($siswa),
            'terdaftar' => $kelompok !== null,
            'daftarUnitKerja' => $kelompok?->perusahaan->daftar_unit_kerja ?? [],
            'kompetensi' => $kelompok === null ? [] : $pendampingan->peta($siswa)
                ->map(fn (array $b) => KompetensiPresenter::baris($b))
                ->values(),
            'kompetensiTerpilih' => $siswa->profilSiswa?->kompetensi_fokus_id,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $siswa = $request->user();
        $profil = $siswa->profilSiswa;
        $kelompok = $profil?->kelompok;

        abort_if($profil === null || $kelompok === null, 403, 'Anda belum terdaftar di kelompok magang.');

        $data = $request->validate([
            'unit_kerja' => [
                Rule::requiredIf($profil->unit_kerja === null),
                'nullable', 'string', Rule::in($kelompok->perusahaan->daftar_unit_kerja),
            ],
            'kompetensi_id' => [
                'required', 'integer',
                Rule::exists('kompetensi', 'id')->where('program_keahlian', $profil->program_keahlian),
            ],
        ], [
            'unit_kerja.required' => 'Pilih unit/bagian kerja Anda.',
            'unit_kerja.in' => 'Pilih unit kerja dari daftar.',
            'kompetensi_id.required' => 'Pilih kompetensi utama Anda.',
            'kompetensi_id.exists' => 'Pilih kompetensi dari daftar.',
        ]);

        $profil->update([
            'unit_kerja' => $data['unit_kerja'] ?? $profil->unit_kerja,
            'kompetensi_fokus_id' => $data['kompetensi_id'],
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => "Selamat datang, {$siswa->name}!"]);

        return to_route('siswa.dashboard');
    }
}
