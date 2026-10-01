<?php

namespace App\Enums;

/**
 * Delapan langkah microlearning per materi (CLAUDE.md bagian 6.4).
 */
enum JenisLangkah: string
{
    case KonsepDasar = 'konsep_dasar';
    case ContohIndustri = 'contoh_industri';
    case MediaVideo = 'media_video';
    case StudiKasus = 'studi_kasus';
    case Latihan = 'latihan';
    case Kuis = 'kuis';
    case PraktikIndustri = 'praktik_industri';
    case Refleksi = 'refleksi';

    public function label(): string
    {
        return match ($this) {
            self::KonsepDasar => 'Konsep Dasar',
            self::ContohIndustri => 'Contoh Industri',
            self::MediaVideo => 'Media/Video',
            self::StudiKasus => 'Studi Kasus',
            self::Latihan => 'Latihan',
            self::Kuis => 'Kuis',
            self::PraktikIndustri => 'Praktik di Industri',
            self::Refleksi => 'Refleksi',
        };
    }

    /**
     * Nomor urut langkah, 1 sampai 8.
     */
    public function urutan(): int
    {
        return array_search($this, self::cases(), true) + 1;
    }
}
