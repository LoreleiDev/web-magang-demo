<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\KelompokMagang;
use App\Models\User;

/**
 * Kelompok dibuat & diatur superadmin; guru hanya melihat kelompok yang ia bimbing.
 */
class KelompokMagangPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(Role::Superadmin, Role::Guru);
    }

    public function view(User $user, KelompokMagang $kelompok): bool
    {
        return $user->hasRole(Role::Superadmin)
            || ($user->hasRole(Role::Guru) && $kelompok->guru_pembimbing_id === $user->id);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(Role::Superadmin);
    }

    public function update(User $user, KelompokMagang $kelompok): bool
    {
        return $user->hasRole(Role::Superadmin);
    }

    public function delete(User $user, KelompokMagang $kelompok): bool
    {
        return $user->hasRole(Role::Superadmin);
    }
}
