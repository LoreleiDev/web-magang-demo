<?php

namespace App\Http\Controllers\Superadmin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Superadmin\KelompokMagangRequest;
use App\Models\KelompokMagang;
use App\Models\Perusahaan;
use App\Models\ProfilSiswa;
use App\Models\ProgramKeahlian;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Kelompok magang sesuai proposal siswa (CLAUDE.md bagian 5.3).
 * Memilih siswa yang sudah ada di kelompok lain = memindahkan siswa tsb.
 */
class KelompokMagangController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', KelompokMagang::class);

        $hariIni = now()->startOfDay();

        $kelompok = KelompokMagang::query()
            ->with(['perusahaan:id,nama', 'guruPembimbing:id,name,status_aktif'])
            ->withCount('profilSiswa')
            ->orderByDesc('periode_mulai')
            ->orderBy('nama_kelompok')
            ->get()
            ->map(fn (KelompokMagang $k) => [
                'id' => $k->id,
                'nama_kelompok' => $k->nama_kelompok,
                'perusahaan' => $k->perusahaan->nama,
                'guru' => $k->guruPembimbing->name,
                'guru_aktif' => $k->guruPembimbing->status_aktif,
                'periode_mulai' => $k->periode_mulai->toDateString(),
                'periode_selesai' => $k->periode_selesai->toDateString(),
                'status_periode' => match (true) {
                    $hariIni->lt($k->periode_mulai) => 'akan_datang',
                    $hariIni->gt($k->periode_selesai) => 'selesai',
                    default => 'berjalan',
                },
                'jumlah_siswa' => $k->profil_siswa_count,
            ]);

        return Inertia::render('superadmin/kelompok/index', [
            'kelompok' => $kelompok,
            'siswaTanpaKelompok' => ProfilSiswa::whereNull('kelompok_id')
                ->whereHas('user', fn ($q) => $q->where('status_aktif', true))
                ->count(),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', KelompokMagang::class);

        return Inertia::render('superadmin/kelompok/form', [
            'kelompok' => null,
            ...$this->opsi(null),
        ]);
    }

    public function store(KelompokMagangRequest $request): RedirectResponse
    {
        Gate::authorize('create', KelompokMagang::class);

        $kelompok = DB::transaction(function () use ($request) {
            $kelompok = KelompokMagang::create($request->safe()->except('siswa_ids'));
            $this->aturAnggota($kelompok, $request->siswaIds());

            return $kelompok;
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => "Kelompok {$kelompok->nama_kelompok} berhasil dibuat."]);

        return to_route('superadmin.kelompok.index');
    }

    public function edit(KelompokMagang $kelompok): Response
    {
        Gate::authorize('update', $kelompok);

        return Inertia::render('superadmin/kelompok/form', [
            'kelompok' => [
                'id' => $kelompok->id,
                'nama_kelompok' => $kelompok->nama_kelompok,
                'perusahaan_id' => $kelompok->perusahaan_id,
                'guru_pembimbing_id' => $kelompok->guru_pembimbing_id,
                'periode_mulai' => $kelompok->periode_mulai->toDateString(),
                'periode_selesai' => $kelompok->periode_selesai->toDateString(),
                'siswa_ids' => $kelompok->profilSiswa()->pluck('user_id'),
            ],
            ...$this->opsi($kelompok),
        ]);
    }

    public function update(KelompokMagangRequest $request, KelompokMagang $kelompok): RedirectResponse
    {
        Gate::authorize('update', $kelompok);

        DB::transaction(function () use ($request, $kelompok) {
            $perusahaanLama = $kelompok->perusahaan_id;

            $kelompok->update($request->safe()->except('siswa_ids'));

            // Unit kerja berasal dari perusahaan, jadi harus dipilih ulang jika perusahaan berubah.
            if ($kelompok->perusahaan_id !== $perusahaanLama) {
                $kelompok->profilSiswa()->update(['unit_kerja' => null]);
            }

            $this->aturAnggota($kelompok, $request->siswaIds());
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Perubahan kelompok disimpan.']);

        return to_route('superadmin.kelompok.index');
    }

    /**
     * Jadikan $siswaIds sebagai anggota kelompok. Siswa yang pindah dari kelompok di
     * perusahaan lain, atau dikeluarkan dari kelompok, harus memilih unit kerja lagi.
     *
     * @param  list<int>  $siswaIds
     */
    private function aturAnggota(KelompokMagang $kelompok, array $siswaIds): void
    {
        $kelompok->profilSiswa()
            ->whereNotIn('user_id', $siswaIds)
            ->update(['kelompok_id' => null, 'unit_kerja' => null]);

        ProfilSiswa::query()
            ->whereIn('user_id', $siswaIds)
            ->with('kelompok:id,perusahaan_id')
            ->get()
            ->each(function (ProfilSiswa $profil) use ($kelompok) {
                if ($profil->kelompok_id === $kelompok->id) {
                    return;
                }

                $pindahPerusahaan = $profil->kelompok?->perusahaan_id !== $kelompok->perusahaan_id;

                $profil->update([
                    'kelompok_id' => $kelompok->id,
                    'unit_kerja' => $pindahPerusahaan ? null : $profil->unit_kerja,
                ]);
            });
    }

    /**
     * Pilihan perusahaan, guru, dan siswa untuk form.
     *
     * @return array<string, mixed>
     */
    private function opsi(?KelompokMagang $kelompok): array
    {
        $program = ProgramKeahlian::opsi();

        return [
            'perusahaan' => Perusahaan::orderBy('nama')->get(['id', 'nama'])
                ->map(fn (Perusahaan $p) => ['id' => $p->id, 'nama' => $p->nama]),
            'guru' => User::query()
                ->where('role', Role::Guru)
                ->where(fn ($q) => $q->where('status_aktif', true)
                    ->when($kelompok, fn ($q) => $q->orWhere('id', $kelompok?->guru_pembimbing_id)))
                ->with('profilGuru')
                ->withCount('kelompokDibimbing')
                ->orderBy('name')
                ->get()
                ->map(fn (User $g) => [
                    'id' => $g->id,
                    'nama' => $g->name,
                    'program_keahlian' => $g->profilGuru?->program_keahlian
                        ? ($program[$g->profilGuru->program_keahlian] ?? null)
                        : null,
                    'jumlah_kelompok' => $g->kelompok_dibimbing_count,
                ]),
            'siswa' => User::query()
                ->where('role', Role::Siswa)
                ->whereHas('profilSiswa')
                // Siswa nonaktif hanya tampil jika sudah menjadi anggota kelompok yang diedit.
                ->where(fn ($q) => $q->where('status_aktif', true)
                    ->when($kelompok, fn ($q) => $q->orWhereHas(
                        'profilSiswa',
                        fn ($q) => $q->where('kelompok_id', $kelompok?->id),
                    )))
                ->with('profilSiswa.kelompok:id,nama_kelompok')
                ->orderBy('name')
                ->get()
                ->map(fn (User $s) => [
                    'id' => $s->id,
                    'nama' => $s->name,
                    'id_siswa' => $s->profilSiswa?->id_siswa,
                    'program_keahlian' => $s->profilSiswa?->program_keahlian,
                    'kelompok_id' => $s->profilSiswa?->kelompok_id,
                    'kelompok_nama' => $s->profilSiswa?->kelompok?->nama_kelompok,
                ]),
        ];
    }
}
