<?php

namespace App\Http\Controllers\Superadmin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Superadmin\AkunRequest;
use App\Models\Perusahaan;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Manajemen akun guru, siswa, dan industri (CLAUDE.md bagian 5.1).
 * Tidak ada hapus akun: akun dinonaktifkan agar riwayat data tetap utuh.
 */
class AkunController extends Controller
{
    private const ROLE_DIKELOLA = [Role::Guru, Role::Siswa, Role::Industri];

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', User::class);

        $role = Role::tryFrom((string) $request->query('role'));
        $role = in_array($role, self::ROLE_DIKELOLA, true) ? $role : null;
        $cari = trim((string) $request->query('cari'));

        $akun = User::query()
            ->whereIn('role', self::ROLE_DIKELOLA)
            ->when($role, fn (Builder $q) => $q->where('role', $role))
            ->when($cari !== '', fn (Builder $q) => $q->where(fn (Builder $q) => $q
                ->where('name', 'like', "%{$cari}%")
                ->orWhere('email', 'like', "%{$cari}%")
                ->orWhereHas('profilSiswa', fn (Builder $q) => $q->where('id_siswa', 'like', "%{$cari}%"))))
            ->with(['profilSiswa.kelompok', 'profilGuru', 'profilIndustri.perusahaan'])
            ->withCount('kelompokDibimbing')
            ->orderBy('status_aktif', 'desc')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (User $user) => $this->baris($user));

        $jumlah = User::query()
            ->whereIn('role', self::ROLE_DIKELOLA)
            ->selectRaw('role, count(*) as total')
            ->groupBy('role')
            ->pluck('total', 'role');

        return Inertia::render('superadmin/akun/index', [
            'akun' => $akun,
            'filter' => ['role' => $role?->value, 'cari' => $cari],
            'jumlah' => [
                'semua' => (int) $jumlah->sum(),
                'guru' => (int) ($jumlah[Role::Guru->value] ?? 0),
                'siswa' => (int) ($jumlah[Role::Siswa->value] ?? 0),
                'industri' => (int) ($jumlah[Role::Industri->value] ?? 0),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        Gate::authorize('create', User::class);

        $role = Role::tryFrom((string) $request->query('role'));

        return Inertia::render('superadmin/akun/form', [
            'akun' => null,
            'roleAwal' => in_array($role, self::ROLE_DIKELOLA, true) ? $role->value : Role::Siswa->value,
            'perusahaan' => $this->opsiPerusahaan(),
        ]);
    }

    public function store(AkunRequest $request): RedirectResponse
    {
        Gate::authorize('create', User::class);

        $data = $request->validated();
        $role = Role::from($data['role']);

        DB::transaction(function () use ($data, $role) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => $role,
                'status_aktif' => true,
            ]);

            $this->simpanProfil($user, $data);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => "Akun {$data['name']} berhasil dibuat."]);

        return to_route('superadmin.akun.index', ['role' => $role->value]);
    }

    public function edit(User $akun): Response
    {
        Gate::authorize('update', $akun);

        $akun->load(['profilSiswa.kelompok.perusahaan', 'profilGuru', 'profilIndustri']);

        return Inertia::render('superadmin/akun/form', [
            'akun' => [
                'id' => $akun->id,
                'name' => $akun->name,
                'email' => $akun->email,
                'role' => $akun->role->value,
                'status_aktif' => $akun->status_aktif,
                'nip' => $akun->profilGuru?->nip,
                'program_keahlian' => $akun->programKeahlian(),
                'id_siswa' => $akun->profilSiswa?->id_siswa,
                'kelompok' => $akun->profilSiswa?->kelompok
                    ? $akun->profilSiswa->kelompok->nama_kelompok.' · '.$akun->profilSiswa->kelompok->perusahaan->nama
                    : null,
                'unit_kerja' => $akun->profilSiswa?->unit_kerja,
                'perusahaan_id' => $akun->profilIndustri?->perusahaan_id,
                'jabatan' => $akun->profilIndustri?->jabatan,
            ],
            'roleAwal' => $akun->role->value,
            'perusahaan' => $this->opsiPerusahaan(),
        ]);
    }

    public function update(AkunRequest $request, User $akun): RedirectResponse
    {
        Gate::authorize('update', $akun);

        $data = $request->validated();

        DB::transaction(function () use ($akun, $data) {
            $akun->fill([
                'name' => $data['name'],
                'email' => $data['email'],
            ]);

            if (filled($data['password'] ?? null)) {
                $akun->password = $data['password'];
            }

            $akun->save();

            $this->simpanProfil($akun, $data);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Perubahan akun disimpan.']);

        return to_route('superadmin.akun.index', ['role' => $akun->role->value]);
    }

    /**
     * Aktifkan / nonaktifkan akun.
     */
    public function ubahStatus(User $akun): RedirectResponse
    {
        Gate::authorize('update', $akun);

        $akun->update(['status_aktif' => ! $akun->status_aktif]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $akun->status_aktif
                ? "Akun {$akun->name} diaktifkan kembali."
                : "Akun {$akun->name} dinonaktifkan. Pengguna ini tidak bisa login lagi.",
        ]);

        return back();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function simpanProfil(User $user, array $data): void
    {
        match ($user->role) {
            Role::Guru => $user->profilGuru()->updateOrCreate([], [
                'nip' => $data['nip'] ?? null,
                'program_keahlian' => $data['program_keahlian'] ?? null,
            ]),
            Role::Siswa => $user->profilSiswa()->updateOrCreate([], [
                'id_siswa' => $data['id_siswa'],
                'program_keahlian' => $data['program_keahlian'],
            ]),
            Role::Industri => $user->profilIndustri()->updateOrCreate([], [
                'perusahaan_id' => $data['perusahaan_id'],
                'jabatan' => $data['jabatan'] ?? null,
            ]),
            Role::Superadmin => null,
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function baris(User $user): array
    {
        $program = config('magang.program_keahlian');

        $keterangan = match ($user->role) {
            Role::Guru => array_filter([
                $user->profilGuru?->program_keahlian ? $program[$user->profilGuru->program_keahlian] ?? null : null,
                $user->kelompok_dibimbing_count.' kelompok dibimbing',
            ]),
            Role::Siswa => array_filter([
                'NIS '.$user->profilSiswa?->id_siswa,
                $user->profilSiswa?->program_keahlian,
                $user->profilSiswa?->kelompok?->nama_kelompok ?? 'Belum masuk kelompok',
            ]),
            Role::Industri => array_filter([
                $user->profilIndustri?->perusahaan->nama,
                $user->profilIndustri?->jabatan,
            ]),
            Role::Superadmin => [],
        };

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role->value,
            'role_label' => $user->role->label(),
            'status_aktif' => $user->status_aktif,
            'keterangan' => array_values($keterangan),
            'perlu_perhatian' => $user->role === Role::Siswa && $user->profilSiswa?->kelompok_id === null,
        ];
    }

    /**
     * @return list<array{id: int, nama: string}>
     */
    private function opsiPerusahaan(): array
    {
        return Perusahaan::orderBy('nama')->get(['id', 'nama'])
            ->map(fn (Perusahaan $p) => ['id' => $p->id, 'nama' => $p->nama])
            ->values()
            ->all();
    }
}
