<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\KelompokMagang;
use App\Models\User;
use App\Services\RingkasanSiswaService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Dashboard guru pembimbing (CLAUDE.md bagian 7): pilih kelompok yang ia bimbing,
 * lalu lihat daftar siswa beserta ringkasannya.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request, RingkasanSiswaService $ringkasan): Response
    {
        $guru = $request->user();

        $kelompok = $guru->kelompokDibimbing()
            ->with('perusahaan:id,nama')
            ->withCount('profilSiswa')
            ->orderByDesc('periode_mulai')
            ->get();

        // Kelompok lain lewat ?kelompok= diabaikan: hanya kelompok yang ia bimbing.
        $terpilih = $kelompok->firstWhere('id', $request->integer('kelompok')) ?? $kelompok->first();

        $siswa = $terpilih
            ? $terpilih->siswa()->with('profilSiswa')->orderBy('name')->get()
                ->map(fn (User $s) => $ringkasan->baris($s))
            : collect();

        return Inertia::render('guru/dashboard', [
            'kelompok' => $kelompok->map(fn (KelompokMagang $k) => [
                'id' => $k->id,
                'nama_kelompok' => $k->nama_kelompok,
                'perusahaan' => $k->perusahaan->nama,
                'periode_mulai' => $k->periode_mulai->toDateString(),
                'periode_selesai' => $k->periode_selesai->toDateString(),
                'jumlah_siswa' => $k->profil_siswa_count,
            ]),
            'kelompokTerpilih' => $terpilih?->id,
            'siswa' => $siswa,
            'programGuru' => $guru->programKeahlian(),
        ]);
    }
}
