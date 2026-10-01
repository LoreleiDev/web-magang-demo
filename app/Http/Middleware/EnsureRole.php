<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Membatasi route untuk role tertentu, contoh: `role:guru` atau `role:superadmin,guru`.
 * Akun yang dinonaktifkan di tengah sesi langsung dikeluarkan.
 */
class EnsureRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->route('login');
        }

        if (! $user->status_aktif) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Akun Anda sudah dinonaktifkan. Silakan hubungi admin sekolah.',
            ]);
        }

        $diizinkan = array_map(fn (string $role) => Role::from($role), $roles);

        abort_unless($user->hasRole(...$diizinkan), 403);

        return $next($request);
    }
}
