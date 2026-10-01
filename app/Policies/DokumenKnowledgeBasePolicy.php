<?php

namespace App\Policies;

use App\Enums\JenisDokumen;
use App\Enums\Role;
use App\Models\DokumenKnowledgeBase;
use App\Models\User;

/**
 * Dokumen sekolah diunggah guru (per program keahliannya);
 * dokumen industri diunggah superadmin (per perusahaan).
 */
class DokumenKnowledgeBasePolicy
{
    public function create(User $user, JenisDokumen $jenis): bool
    {
        return match ($jenis) {
            JenisDokumen::Sekolah => $user->hasRole(Role::Guru) && $user->programKeahlian() !== null,
            JenisDokumen::Industri => $user->hasRole(Role::Superadmin),
        };
    }

    public function delete(User $user, DokumenKnowledgeBase $dokumen): bool
    {
        return match ($dokumen->jenis) {
            JenisDokumen::Sekolah => $user->hasRole(Role::Guru)
                && $user->programKeahlian() === $dokumen->program_keahlian,
            JenisDokumen::Industri => $user->hasRole(Role::Superadmin),
        };
    }
}
