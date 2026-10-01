<?php

namespace App\Policies;

use App\Models\HasilAssessment;
use App\Models\User;

class HasilAssessmentPolicy
{
    /**
     * Superadmin semua, guru kelompoknya, industri perusahaannya, siswa miliknya.
     */
    public function view(User $user, HasilAssessment $hasil): bool
    {
        return $hasil->siswa->dapatDilihatOleh($user);
    }
}
