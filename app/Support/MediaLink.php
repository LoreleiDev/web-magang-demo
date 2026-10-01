<?php

namespace App\Support;

use App\Enums\JenisMedia;

/**
 * Validasi & pengambilan ID dari link media materi (CLAUDE.md bagian 6.4.1).
 *
 * - Video: hanya YouTube (watch?v=, youtu.be/, shorts/, embed/).
 * - Gambar & dokumen: hanya Google Drive / Google Docs.
 */
final class MediaLink
{
    private const HOST_YOUTUBE = ['youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtu.be'];

    private const HOST_DRIVE = ['drive.google.com', 'docs.google.com'];

    /**
     * ID video YouTube atau ID file Google Drive, atau null jika link tidak sesuai jenisnya.
     */
    public static function ambilId(JenisMedia $jenis, string $url): ?string
    {
        $bagian = parse_url(trim($url));

        if (! is_array($bagian) || ! in_array($bagian['scheme'] ?? '', ['http', 'https'], true)) {
            return null;
        }

        $host = strtolower($bagian['host'] ?? '');
        $path = $bagian['path'] ?? '';
        parse_str($bagian['query'] ?? '', $query);

        return match ($jenis) {
            JenisMedia::Youtube => self::idYoutube($host, $path, $query),
            JenisMedia::Gambar, JenisMedia::Dokumen => self::idDrive($host, $path, $query),
        };
    }

    /**
     * URL untuk iframe.
     */
    public static function urlEmbed(JenisMedia $jenis, string $id): string
    {
        return match ($jenis) {
            JenisMedia::Youtube => "https://www.youtube-nocookie.com/embed/{$id}",
            JenisMedia::Gambar, JenisMedia::Dokumen => "https://drive.google.com/file/d/{$id}/preview",
        };
    }

    /**
     * URL untuk tombol "Buka di Google Drive" / buka di YouTube.
     */
    public static function urlBuka(JenisMedia $jenis, string $id): string
    {
        return match ($jenis) {
            JenisMedia::Youtube => "https://www.youtube.com/watch?v={$id}",
            JenisMedia::Gambar, JenisMedia::Dokumen => "https://drive.google.com/file/d/{$id}/view",
        };
    }

    public static function pesanError(JenisMedia $jenis): string
    {
        return match ($jenis) {
            JenisMedia::Youtube => 'Link video harus berupa link YouTube, misalnya https://www.youtube.com/watch?v=… atau https://youtu.be/….',
            JenisMedia::Gambar, JenisMedia::Dokumen => 'Link harus berupa link Google Drive atau Google Docs, misalnya https://drive.google.com/file/d/…/view.',
        };
    }

    /**
     * @param  array<array-key, mixed>  $query
     */
    private static function idYoutube(string $host, string $path, array $query): ?string
    {
        if (! in_array($host, self::HOST_YOUTUBE, true)) {
            return null;
        }

        $kandidat = match (true) {
            $host === 'youtu.be' => explode('/', ltrim($path, '/'))[0],
            $path === '/watch' => $query['v'] ?? null,
            (bool) preg_match('#^/(shorts|embed|live)/([^/]+)#', $path, $m) => $m[2],
            default => null,
        };

        return is_string($kandidat) && preg_match('/^[A-Za-z0-9_-]{11}$/', $kandidat) ? $kandidat : null;
    }

    /**
     * @param  array<array-key, mixed>  $query
     */
    private static function idDrive(string $host, string $path, array $query): ?string
    {
        if (! in_array($host, self::HOST_DRIVE, true)) {
            return null;
        }

        $kandidat = match (true) {
            (bool) preg_match('#/d/([^/]+)#', $path, $m) => $m[1],
            in_array($path, ['/open', '/uc'], true) => $query['id'] ?? null,
            default => null,
        };

        return is_string($kandidat) && preg_match('/^[A-Za-z0-9_-]{10,}$/', $kandidat) ? $kandidat : null;
    }
}
