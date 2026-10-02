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
 * Login email + password untuk superadmin, guru, dan industri. Siswa memakai
 * halaman login khusus (SiswaLoginController). Tidak ada registrasi mandiri.
 */
class AuthenticatedSessionController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('auth/login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate(
            [Role::Superadmin, Role::Guru, Role::Industri],
            'Akun siswa masuk lewat halaman Login Siswa.',
        );

        $request->session()->regenerate();

        return redirect()->intended(route($request->user()->role->homeRoute()));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $siswa = $request->user()?->hasRole(Role::Siswa) ?? false;

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route($siswa ? 'siswa.login' : 'login');
    }
}
