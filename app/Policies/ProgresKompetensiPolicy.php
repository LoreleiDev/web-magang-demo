<?php

namespace App\Policies;

use App\Enums\Role;
use App\Enums\StatusKompetensi;
use App\Models\ProgresKompetensi;
use App\Models\User;

/**
 * CLAUDE.md bagian 11.1: level diisi guru pembimbing, verifikasi oleh industri.
 */
class ProgresKompetensiPolicy
{
    public function view(User $user, ProgresKompetensi $progres): bool
    {
        return $progres->siswa->dapatDilihatOleh($user);
    }

    /**
     * Guru mengisi level 1-4 hanya untuk siswa di kelompok yang ia bimbing.
     */
    public function isiLevel(User $user, ProgresKompetensi $progres): bool
    {
        return $user->hasRole(Role::Guru) && $progres->siswa->dapatDilihatOleh($user);
    }

    /**
     * Industri memverifikasi hanya siswa di perusahaannya, dan hanya saat
     * status "Menunggu verifikasi" (keputusan 13 no. 14).
     */
    public function verifikasi(User $user, ProgresKompetensi $progres): bool
    {
        return $user->hasRole(Role::Industri)
            && $progres->status === StatusKompetensi::MenungguVerifikasi
            && $progres->siswa->dapatDilihatOleh($user);
    }
}
