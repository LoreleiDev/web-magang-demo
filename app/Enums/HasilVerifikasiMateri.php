<?php

namespace App\Enums;

enum HasilVerifikasiMateri: string
{
    case Diverifikasi = 'diverifikasi';
    case BelumSesuai = 'belum_sesuai';

    public function label(): string
    {
        return match ($this) {
            self::Diverifikasi => 'Diverifikasi',
            self::BelumSesuai => 'Belum Sesuai',
        };
    }
}
