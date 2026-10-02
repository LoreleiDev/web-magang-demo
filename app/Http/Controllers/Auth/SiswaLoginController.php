<?php

namespace App\Http\Controllers\Auth;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Halaman login khusus siswa (keputusan 13 no. 18). Setelah login siswa selalu
 * diarahkan ke Mulai Pendampingan untuk memilih kompetensi yang dijalani.
 */
class SiswaLoginController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('auth/login-siswa');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate(
            [Role::Siswa],
            'Halaman ini khusus siswa. Guru, industri, dan admin masuk lewat halaman login utama.',
        );

        $request->session()->regenerate();

        return redirect()->route('siswa.mulai');
    }
}
