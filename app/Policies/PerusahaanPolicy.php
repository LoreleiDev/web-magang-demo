<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Perusahaan;
use App\Models\User;

/**
 * Data perusahaan beserta dokumen industrinya dikelola superadmin saja.
 */
class PerusahaanPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(Role::Superadmin);
    }

    public function view(User $user, Perusahaan $perusahaan): bool
    {
        return $user->hasRole(Role::Superadmin);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(Role::Superadmin);
    }

    public function update(User $user, Perusahaan $perusahaan): bool
    {
        return $user->hasRole(Role::Superadmin);
    }

    public function delete(User $user, Perusahaan $perusahaan): bool
    {
        return $user->hasRole(Role::Superadmin);
    }
}
