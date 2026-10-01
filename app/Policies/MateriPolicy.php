<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Materi;
use App\Models\User;

/**
 * Hak akses materi (CLAUDE.md bagian 2.1, keputusan 13 no. 16 & 17).
 */
class MateriPolicy
{
    public function view(User $user, Materi $materi): bool
    {
        $program = $materi->kompetensi->program_keahlian;

        return match ($user->role) {
            Role::Superadmin, Role::Guru => true,
            Role::Industri => in_array($program, $user->programKeahlianSiswaPerusahaan(), true),
            Role::Siswa => $user->programKeahlian() === $program,
        };
    }

    public function create(User $user): bool
    {
        return $user->hasRole(Role::Guru) && $user->programKeahlian() !== null;
    }

    public function update(User $user, Materi $materi): bool
    {
        return $user->hasRole(Role::Guru)
            && $user->programKeahlian() === $materi->kompetensi->program_keahlian;
    }

    /**
     * Industri memverifikasi / memberi masukan untuk materi program keahlian
     * siswa yang magang di perusahaannya.
     */
    public function verifikasi(User $user, Materi $materi): bool
    {
        return $user->hasRole(Role::Industri)
            && in_array($materi->kompetensi->program_keahlian, $user->programKeahlianSiswaPerusahaan(), true);
    }
}
