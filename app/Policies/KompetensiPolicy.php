<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Kompetensi;
use App\Models\User;

/**
 * Kompetensi diinput & diedit guru, hanya untuk program keahliannya (keputusan 13 no. 16).
 */
class KompetensiPolicy
{
    public function create(User $user): bool
    {
        return $user->hasRole(Role::Guru) && $user->programKeahlian() !== null;
    }

    public function update(User $user, Kompetensi $kompetensi): bool
    {
        return $user->hasRole(Role::Guru)
            && $user->programKeahlian() === $kompetensi->program_keahlian;
    }
}
