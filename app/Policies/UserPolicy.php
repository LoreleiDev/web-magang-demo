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
     * Hapus akun guru, siswa, industri (revisi keputusan 25). Akun superadmin tidak bisa dihapus.
     */
    public function delete(User $user, User $target): bool
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

    /**
     * Pembimbing industri mengubah unit kerja siswa yang magang di perusahaannya
     * (revisi 4 Okt 2026). Siswa tetap bisa mengganti sendiri (keputusan 34).
     */
    public function ubahUnitKerja(User $user, User $siswa): bool
    {
        return $user->hasRole(Role::Industri)
            && $siswa->hasRole(Role::Siswa)
            && $user->perusahaanId() !== null
            && $siswa->perusahaanId() === $user->perusahaanId();
    }
}
