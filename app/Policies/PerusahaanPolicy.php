<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Perusahaan;
use App\Models\User;

/**
 * Data perusahaan beserta dokumen industrinya dikelola superadmin. Pembimbing industri
 * boleh menambah & menghapus unit kerja perusahaannya sendiri (revisi 4 Okt 2026).
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

    public function kelolaUnitKerja(User $user, Perusahaan $perusahaan): bool
    {
        return $user->hasRole(Role::Superadmin)
            || ($user->hasRole(Role::Industri) && $user->perusahaanId() === $perusahaan->id);
    }
}
