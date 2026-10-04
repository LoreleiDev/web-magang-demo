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
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Detail siswa untuk industri + tombol "Verifikasi Kompetensi" (bagian 8 & 11.1),
 * serta ubah unit kerja siswa (revisi 4 Okt 2026).
 */
class SiswaController extends Controller
{
    public function show(User $siswa, RingkasanSiswaService $ringkasan): Response
    {
        Gate::authorize('lihatSiswa', $siswa);

        return Inertia::render('industri/siswa', [
            ...$ringkasan->detail($siswa),
            'daftarUnitKerja' => $siswa->profilSiswa?->kelompok?->perusahaan->daftar_unit_kerja ?? [],
        ]);
    }

    /**
     * Ubah unit kerja siswa di perusahaannya, hanya dari daftar unit perusahaan.
     */
    public function unitKerja(Request $request, User $siswa): RedirectResponse
    {
        Gate::authorize('ubahUnitKerja', $siswa);

        $data = $request->validate([
            'unit_kerja' => ['required', 'string', Rule::in($siswa->profilSiswa?->kelompok?->perusahaan->daftar_unit_kerja ?? [])],
        ], ['unit_kerja.in' => 'Pilih unit kerja dari daftar perusahaan.']);

        $siswa->profilSiswa?->update(['unit_kerja' => $data['unit_kerja']]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Unit kerja {$siswa->name} diganti ke {$data['unit_kerja']}.",
        ]);

        return back();
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
