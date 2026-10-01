<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Logbook;
use App\Models\User;

class LogbookPolicy
{
    /**
     * Superadmin semua, guru kelompoknya, industri perusahaannya, siswa miliknya.
     */
    public function view(User $user, Logbook $logbook): bool
    {
        return $logbook->siswa->dapatDilihatOleh($user);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(Role::Siswa);
    }

    public function update(User $user, Logbook $logbook): bool
    {
        return $user->hasRole(Role::Siswa) && $logbook->siswa_id === $user->id;
    }
}
