<?php

namespace App\Http\Controllers\Superadmin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\KelompokMagang;
use App\Models\Materi;
use App\Models\Perusahaan;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Ringkasan Panel Superadmin.
 */
class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $jumlahAkun = User::query()
            ->where('status_aktif', true)
            ->selectRaw('role, count(*) as total')
            ->groupBy('role')
            ->pluck('total', 'role');

        $hariIni = now()->toDateString();

        $siswaTanpaKelompok = User::query()
            ->where('role', Role::Siswa)
            ->where('status_aktif', true)
            ->whereHas('profilSiswa', fn (Builder $q) => $q->whereNull('kelompok_id'))
            ->with('profilSiswa')
            ->orderBy('name')
            ->limit(6)
            ->get()
            ->map(fn (User $s) => [
                'id' => $s->id,
                'nama' => $s->name,
                'id_siswa' => $s->profilSiswa?->id_siswa,
                'program_keahlian' => $s->profilSiswa?->program_keahlian,
            ]);

        return Inertia::render('superadmin/dashboard', [
            'statistik' => [
                'guru' => (int) ($jumlahAkun[Role::Guru->value] ?? 0),
                'siswa' => (int) ($jumlahAkun[Role::Siswa->value] ?? 0),
                'industri' => (int) ($jumlahAkun[Role::Industri->value] ?? 0),
                'perusahaan' => Perusahaan::count(),
                'kelompok_berjalan' => KelompokMagang::where('periode_mulai', '<=', $hariIni)
                    ->where('periode_selesai', '>=', $hariIni)
                    ->count(),
                'kelompok_total' => KelompokMagang::count(),
                'materi' => Materi::count(),
                'materi_terverifikasi' => Materi::where('terverifikasi_industri', true)->count(),
            ],
            'siswaTanpaKelompok' => $siswaTanpaKelompok,
            'jumlahSiswaTanpaKelompok' => User::query()
                ->where('role', Role::Siswa)
                ->where('status_aktif', true)
                ->whereHas('profilSiswa', fn (Builder $q) => $q->whereNull('kelompok_id'))
                ->count(),
        ]);
    }
}
