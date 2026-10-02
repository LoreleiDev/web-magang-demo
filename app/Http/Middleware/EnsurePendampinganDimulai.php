<?php

namespace App\Http\Middleware;

use App\Models\ProfilSiswa;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Halaman siswa hanya bisa dibuka setelah Mulai Pendampingan selesai: siswa sudah
 * masuk kelompok, memilih unit kerja, dan memilih kompetensi utama. Pilihan ini
 * disimpan permanen, jadi tidak diminta ulang setiap login (keputusan 13 no. 18).
 */
class EnsurePendampinganDimulai
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! self::selesai($request->user()?->profilSiswa)) {
            return redirect()->route('siswa.mulai');
        }

        return $next($request);
    }

    public static function selesai(?ProfilSiswa $profil): bool
    {
        return $profil !== null
            && $profil->kelompok_id !== null
            && $profil->unit_kerja !== null
            && $profil->kompetensi_fokus_id !== null;
    }
}
