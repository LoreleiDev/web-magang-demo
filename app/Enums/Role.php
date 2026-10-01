<?php

namespace App\Enums;

enum Role: string
{
    case Superadmin = 'superadmin';
    case Guru = 'guru';
    case Industri = 'industri';
    case Siswa = 'siswa';

    public function label(): string
    {
        return match ($this) {
            self::Superadmin => 'Superadmin',
            self::Guru => 'Guru Pembimbing',
            self::Industri => 'Pembimbing Industri',
            self::Siswa => 'Siswa',
        };
    }

    /**
     * Nama route halaman awal setelah login (CLAUDE.md bagian 4).
     */
    public function homeRoute(): string
    {
        return match ($this) {
            self::Superadmin => 'superadmin.dashboard',
            self::Guru => 'guru.dashboard',
            self::Industri => 'industri.dashboard',
            self::Siswa => 'siswa.dashboard',
        };
    }
}
