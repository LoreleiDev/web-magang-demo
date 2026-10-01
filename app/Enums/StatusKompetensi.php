<?php

namespace App\Enums;

/**
 * Status ProgresKompetensi (CLAUDE.md bagian 10).
 */
enum StatusKompetensi: string
{
    case BelumDipelajari = 'belum_dipelajari';
    case SedangDipelajari = 'sedang_dipelajari';
    case SedangDipraktikkan = 'sedang_dipraktikkan';
    case MenungguVerifikasi = 'menunggu_verifikasi';
    case Terverifikasi = 'terverifikasi';

    public function label(): string
    {
        return match ($this) {
            self::BelumDipelajari => 'Belum dipelajari',
            self::SedangDipelajari => 'Sedang dipelajari',
            self::SedangDipraktikkan => 'Sedang dipraktikkan',
            self::MenungguVerifikasi => 'Menunggu verifikasi',
            self::Terverifikasi => 'Terverifikasi',
        };
    }

    public function urutan(): int
    {
        return match ($this) {
            self::BelumDipelajari => 1,
            self::SedangDipelajari => 2,
            self::SedangDipraktikkan => 3,
            self::MenungguVerifikasi => 4,
            self::Terverifikasi => 5,
        };
    }
}
