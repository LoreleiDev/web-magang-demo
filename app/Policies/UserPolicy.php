<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\User;

/**
 * Manajemen akun (hanya superadmin) dan siapa boleh melihat data seorang siswa.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(Role::Superadmin);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(Role::Superadmin);
    }

    /**
     * Edit dan nonaktifkan akun guru, siswa, industri. Akun superadmin tidak dikelola dari panel.
     */
    public function update(User $user, User $target): bool
    {
        return $user->hasRole(Role::Superadmin) && ! $target->hasRole(Role::Superadmin);
    }

    /**
     * Melihat data (progress, logbook, assessment) seorang siswa.
     */
    public function lihatSiswa(User $user, User $siswa): bool
    {
        return $siswa->hasRole(Role::Siswa) && $siswa->dapatDilihatOleh($user);
    }
}
