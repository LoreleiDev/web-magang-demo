<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Halaman siswa hanya bisa dibuka setelah siswa memilih kompetensi di
 * Mulai Pendampingan pada sesi login ini (bagian 4.1, keputusan 13 no. 18).
 */
class EnsurePendampinganDimulai
{
    public const KUNCI_SESI = 'pendampingan.kompetensi_id';

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $profil = $request->user()?->profilSiswa;

        if ($profil?->kelompok_id === null || ! $request->session()->has(self::KUNCI_SESI)) {
            return redirect()->route('siswa.mulai');
        }

        return $next($request);
    }
}
