<?php

namespace App\Http\Controllers\Auth;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Login terpisah per role (keputusan 13 no. 32): `/login` siswa, `/login/guru`,
 * `/login/industri`, `/login/admin`. Setiap halaman hanya menerima role-nya.
 * Tidak ada registrasi mandiri.
 */
class AuthenticatedSessionController extends Controller
{
    public function create(Request $request): Response
    {
        $role = $this->role($request);

        return Inertia::render('auth/login', [
            'portal' => $role->value,
            'judul' => match ($role) {
                Role::Siswa => 'Masuk sebagai siswa',
                Role::Guru => 'Masuk sebagai guru pembimbing',
                Role::Industri => 'Masuk sebagai pembimbing industri',
                Role::Superadmin => 'Masuk sebagai admin sekolah',
            },
            'aksi' => route($this->routeLogin($role).'.store'),
        ]);
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $role = $this->role($request);

        $request->authenticate([$role], 'Email atau kata sandi salah.');

        $request->session()->regenerate();

        return redirect()->intended(route($role->homeRoute()));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $role = $request->user()->role;

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route($this->routeLogin($role));
    }

    public static function routeLogin(Role $role): string
    {
        return match ($role) {
            Role::Siswa => 'login',
            Role::Guru => 'login.guru',
            Role::Industri => 'login.industri',
            Role::Superadmin => 'login.admin',
        };
    }

    private function role(Request $request): Role
    {
        return Role::from((string) $request->route('portal', Role::Siswa->value));
    }
}
