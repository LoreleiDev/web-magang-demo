<?php

namespace App\Http\Controllers\Industri;

use App\Enums\StatusKompetensi;
use App\Http\Controllers\Controller;
use App\Models\KelompokMagang;
use App\Models\ProgresKompetensi;
use App\Models\User;
use App\Services\RingkasanSiswaService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Dashboard pembimbing industri (bagian 8): siswa dari semua kelompok yang
 * magang di perusahaannya.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request, RingkasanSiswaService $ringkasan): Response
    {
        $industri = $request->user();
        $industri->loadMissing('profilIndustri.perusahaan');
        $perusahaanId = $industri->perusahaanId();

        $siswa = User::query()
            ->siswaDalamCakupan($industri)
            ->with('profilSiswa.kelompok:id,nama_kelompok')
            ->orderBy('name')
            ->get();

        return Inertia::render('industri/dashboard', [
            'perusahaan' => $industri->profilIndustri?->perusahaan->nama,
            'kelompok' => KelompokMagang::where('perusahaan_id', $perusahaanId)
                ->orderByDesc('periode_mulai')
                ->get(['id', 'nama_kelompok'])
                ->map(fn (KelompokMagang $k) => ['id' => $k->id, 'nama' => $k->nama_kelompok]),
            'siswa' => $siswa->map(fn (User $s) => [
                ...$ringkasan->baris($s),
                'kelompok_id' => $s->profilSiswa?->kelompok_id,
            ]),
            'menungguVerifikasi' => ProgresKompetensi::query()
                ->where('status', StatusKompetensi::MenungguVerifikasi)
                ->whereHas('siswa', fn (Builder $q) => $q->siswaDalamCakupan($industri))
                ->count(),
        ]);
    }
}
