<?php

namespace App\Http\Controllers\Superadmin;

use App\Enums\Role;
use App\Exceptions\ImporAkunException;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ImporAkunService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Tambah akun lewat file Excel/CSV sesuai template (revisi 11 Okt 2026).
 */
class ImporAkunController extends Controller
{
    private const ROLE = [Role::Guru, Role::Siswa, Role::Industri];

    public function template(string $role, ImporAkunService $impor): BinaryFileResponse
    {
        Gate::authorize('create', User::class);

        $role = $this->role($role);
        $path = tempnam(sys_get_temp_dir(), 'tpl').'.xlsx';
        $impor->template($role, $path);

        return response()->download($path, "template-akun-{$role->value}.xlsx")->deleteFileAfterSend();
    }

    public function store(Request $request, ImporAkunService $impor): RedirectResponse
    {
        Gate::authorize('create', User::class);

        $data = $request->validate([
            'role' => ['required', Rule::in(array_map(fn (Role $r) => $r->value, self::ROLE))],
            'file' => ['required', 'file', 'extensions:xlsx,csv', 'max:2048'],
        ], [
            'file.required' => 'Pilih file Excel (.xlsx) atau CSV terlebih dahulu.',
            'file.extensions' => 'File harus berformat .xlsx atau .csv (bukan .xls atau .sql).',
            'file.max' => 'Ukuran file maksimal 2 MB.',
        ]);
        $role = Role::from($data['role']);

        try {
            $baris = $impor->baca($request->file('file'), $role);
        } catch (ImporAkunException $e) {
            return back()->withErrors(['file' => $e->getMessage()]);
        }

        $error = $impor->validasi($role, $baris);

        if ($error !== []) {
            Inertia::flash('hasilImpor', [
                'berhasil' => false,
                'role' => $role->value,
                'total' => count($baris),
                'error' => array_map(
                    fn (int $nomor, array $pesan) => ['baris' => $nomor, 'pesan' => $pesan],
                    array_keys($error),
                    array_values($error),
                ),
            ]);

            return back();
        }

        $akun = $impor->impor($role, $baris);

        Inertia::flash('toast', ['type' => 'success', 'message' => count($akun).' akun '.$role->label().' berhasil ditambahkan.']);
        Inertia::flash('hasilImpor', [
            'berhasil' => true,
            'role' => $role->value,
            'total' => count($akun),
            'akun' => $akun,
        ]);

        return back();
    }

    private function role(string $nilai): Role
    {
        $role = Role::tryFrom($nilai);
        abort_unless(in_array($role, self::ROLE, true), 404);

        return $role;
    }
}
