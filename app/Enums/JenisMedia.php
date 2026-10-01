<?php

namespace App\Enums;

/**
 * Media materi berbasis link (CLAUDE.md bagian 6.4.1).
 */
enum JenisMedia: string
{
    case Youtube = 'youtube';
    case Gambar = 'gambar';
    case Dokumen = 'dokumen';

    public function label(): string
    {
        return match ($this) {
            self::Youtube => 'Video YouTube',
            self::Gambar => 'Gambar (Google Drive)',
            self::Dokumen => 'Dokumen (Google Drive)',
        };
    }
}
