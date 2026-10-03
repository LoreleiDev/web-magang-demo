<?php

namespace App\Http\Controllers\Industri;

use App\Enums\StatusKompetensi;
use App\Http\Controllers\Controller;
use App\Models\Kompetensi;
use App\Models\ProgresKompetensi;
use App\Models\User;
use App\Services\RingkasanSiswaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Detail siswa untuk industri + tombol "Verifikasi Kompetensi" (bagian 8 & 11.1).
 */
class SiswaController extends Controller
{
    public function show(User $siswa, RingkasanSiswaService $ringkasan): Response
    {
        Gate::authorize('lihatSiswa', $siswa);

        return Inertia::render('industri/siswa', $ringkasan->detail($siswa));
    }

    /**
     * Hanya untuk siswa di perusahaannya dan saat status "Menunggu verifikasi"
     * (keputusan 13 no. 14). Simpan siapa yang memverifikasi dan kapan.
     */
    public function verifikasi(Request $request, User $siswa, Kompetensi $kompetensi): RedirectResponse
    {
        $progres = ProgresKompetensi::query()
            ->where('siswa_id', $siswa->id)
            ->where('kompetensi_id', $kompetensi->id)
            ->firstOrFail();

        Gate::authorize('verifikasi', $progres);

        $progres->update([
            'status' => StatusKompetensi::Terverifikasi,
            'diverifikasi_oleh' => $request->user()->id,
            'nama_verifikator' => $request->user()->name,
            'tanggal_verifikasi' => now(),
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Kompetensi {$kompetensi->nama_kompetensi_sekolah} milik {$siswa->name} terverifikasi.",
        ]);

        return back();
    }
}
