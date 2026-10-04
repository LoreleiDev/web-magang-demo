<?php

namespace App\Http\Controllers\Industri;

use App\Http\Controllers\Controller;
use App\Models\Perusahaan;
use App\Models\ProfilSiswa;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Pembimbing industri menambah & menghapus unit kerja perusahaannya sendiri (revisi 4 Okt 2026),
 * tanpa harus melalui superadmin. Aturannya sama dengan form perusahaan superadmin.
 */
class UnitKerjaController extends Controller
{
    private const MAKS_UNIT = 30;

    public function index(Request $request): Response
    {
        $perusahaan = $this->perusahaan($request);

        $jumlahSiswa = ProfilSiswa::query()
            ->whereHas('kelompok', fn (Builder $q) => $q->where('perusahaan_id', $perusahaan->id))
            ->whereNotNull('unit_kerja')
            ->selectRaw('unit_kerja, count(*) as total')
            ->groupBy('unit_kerja')
            ->pluck('total', 'unit_kerja');

        return Inertia::render('industri/unit-kerja', [
            'perusahaan' => $perusahaan->nama,
            'unitKerja' => array_map(fn (string $unit) => [
                'nama' => $unit,
                'jumlah_siswa' => (int) ($jumlahSiswa[$unit] ?? 0),
            ], $perusahaan->daftar_unit_kerja),
            'maksUnit' => self::MAKS_UNIT,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $perusahaan = $this->perusahaan($request);

        $nama = trim((string) $request->validate([
            'nama' => ['required', 'string', 'max:100'],
        ], [
            'nama.required' => 'Tulis nama unit kerja.',
            'nama.max' => 'Nama unit kerja maksimal 100 karakter.',
        ])['nama']);

        DB::transaction(function () use ($perusahaan, $nama) {
            // Kunci baris agar dua penambahan bersamaan tidak saling menimpa.
            $perusahaan = Perusahaan::whereKey($perusahaan->id)->lockForUpdate()->firstOrFail();
            $daftar = $perusahaan->daftar_unit_kerja;

            if (in_array(mb_strtolower($nama), array_map('mb_strtolower', $daftar), true)) {
                throw ValidationException::withMessages(['nama' => "Unit kerja \"{$nama}\" sudah ada."]);
            }

            if (count($daftar) >= self::MAKS_UNIT) {
                throw ValidationException::withMessages(['nama' => 'Maksimal '.self::MAKS_UNIT.' unit kerja per perusahaan.']);
            }

            $perusahaan->update(['daftar_unit_kerja' => [...$daftar, $nama]]);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => "Unit kerja {$nama} ditambahkan. Siswa sudah bisa memilihnya."]);

        return to_route('industri.unit-kerja.index');
    }

    /**
     * Unit kerja yang masih dipilih siswa tidak bisa dihapus (keputusan 26), dan
     * perusahaan harus tetap punya minimal satu unit kerja.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $perusahaan = $this->perusahaan($request);
        $nama = (string) $request->validate(['nama' => ['required', 'string']])['nama'];

        DB::transaction(function () use ($perusahaan, $nama) {
            $perusahaan = Perusahaan::whereKey($perusahaan->id)->lockForUpdate()->firstOrFail();
            $daftar = $perusahaan->daftar_unit_kerja;

            if (! in_array($nama, $daftar, true)) {
                throw ValidationException::withMessages(['hapus' => "Unit kerja \"{$nama}\" tidak ditemukan."]);
            }

            $jumlahSiswa = ProfilSiswa::query()
                ->where('unit_kerja', $nama)
                ->whereHas('kelompok', fn (Builder $q) => $q->where('perusahaan_id', $perusahaan->id))
                ->count();

            if ($jumlahSiswa > 0) {
                throw ValidationException::withMessages([
                    'hapus' => "Unit kerja \"{$nama}\" masih dipilih {$jumlahSiswa} siswa sehingga belum bisa dihapus.",
                ]);
            }

            if (count($daftar) === 1) {
                throw ValidationException::withMessages(['hapus' => 'Perusahaan harus punya minimal satu unit kerja.']);
            }

            $perusahaan->update(['daftar_unit_kerja' => array_values(array_diff($daftar, [$nama]))]);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => "Unit kerja {$nama} dihapus."]);

        return to_route('industri.unit-kerja.index');
    }

    private function perusahaan(Request $request): Perusahaan
    {
        $perusahaan = $request->user()->profilIndustri?->perusahaan;

        abort_if($perusahaan === null, 403, 'Akun ini belum terhubung ke perusahaan.');
        Gate::authorize('kelolaUnitKerja', $perusahaan);

        return $perusahaan;
    }
}
