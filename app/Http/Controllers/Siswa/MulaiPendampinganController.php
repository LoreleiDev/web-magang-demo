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

/**
 * Halaman awal siswa (bagian 4.1): data terkunci dari akun & kelompok, pilih unit
 * kerja (sekali), lalu pilih kompetensi yang dijalani (setiap login).
 */
class MulaiPendampinganController extends Controller
{
    public function show(Request $request, PendampinganSiswaService $pendampingan): Response
    {
        $siswa = $request->user();
        $kelompok = $siswa->profilSiswa?->kelompok;

        return Inertia::render('siswa/mulai', [
            'konteks' => $pendampingan->konteks($siswa),
            'terdaftar' => $kelompok !== null,
            'daftarUnitKerja' => $kelompok?->perusahaan->daftar_unit_kerja ?? [],
            'kompetensi' => $kelompok === null ? [] : $pendampingan->peta($siswa)
                ->map(fn (array $b) => KompetensiPresenter::baris($b))
                ->values(),
            'kompetensiTerpilih' => $request->session()->get(EnsurePendampinganDimulai::KUNCI_SESI)
                ?? $siswa->profilSiswa?->kompetensi_fokus_id,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $siswa = $request->user();
        $profil = $siswa->profilSiswa;
        $kelompok = $profil?->kelompok;

        abort_if($profil === null || $kelompok === null, 403, 'Anda belum terdaftar di kelompok magang.');

        $data = $request->validate([
            // Unit kerja hanya dipilih sekali, dari daftar unit kerja perusahaan.
            'unit_kerja' => [
                Rule::requiredIf($profil->unit_kerja === null),
                Rule::prohibitedIf($profil->unit_kerja !== null),
                'nullable', 'string', Rule::in($kelompok->perusahaan->daftar_unit_kerja),
            ],
            'kompetensi_id' => [
                'required', 'integer',
                Rule::exists('kompetensi', 'id')->where('program_keahlian', $profil->program_keahlian),
            ],
        ], [
            'unit_kerja.required' => 'Pilih unit/bagian kerja Anda.',
            'unit_kerja.in' => 'Pilih unit kerja dari daftar.',
            'unit_kerja.prohibited' => 'Unit kerja sudah dipilih sebelumnya.',
            'kompetensi_id.required' => 'Pilih kompetensi yang ingin Anda jalani.',
            'kompetensi_id.exists' => 'Pilih kompetensi dari daftar.',
        ]);

        $profil->update([
            'unit_kerja' => $profil->unit_kerja ?? $data['unit_kerja'],
            'kompetensi_fokus_id' => $data['kompetensi_id'],
        ]);

        $request->session()->put(EnsurePendampinganDimulai::KUNCI_SESI, (int) $data['kompetensi_id']);

        Inertia::flash('toast', ['type' => 'success', 'message' => "Selamat datang, {$siswa->name}!"]);

        return to_route('siswa.dashboard');
    }
}
