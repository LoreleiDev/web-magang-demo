import type { JenisMedia } from '@/components/media-embed';

/**
 * Versi frontend dari App\Support\MediaLink, hanya untuk pratinjau di form guru.
 * Validasi yang menentukan tetap di backend.
 */
const HOST_YOUTUBE = [
    'youtube.com',
    'www.youtube.com',
    'm.youtube.com',
    'youtu.be',
];
const HOST_DRIVE = ['drive.google.com', 'docs.google.com'];

export function ambilIdMedia(jenis: JenisMedia, url: string): string | null {
    let u: URL;

    try {
        u = new URL(url.trim());
    } catch {
        return null;
    }

    if (!['http:', 'https:'].includes(u.protocol)) {
        return null;
    }

    const host = u.hostname.toLowerCase();

    if (jenis === 'youtube') {
        if (!HOST_YOUTUBE.includes(host)) {
            return null;
        }

        const kandidat =
            host === 'youtu.be'
                ? u.pathname.slice(1).split('/')[0]
                : u.pathname === '/watch'
                  ? u.searchParams.get('v')
                  : (u.pathname.match(/^\/(shorts|embed|live)\/([^/]+)/)?.[2] ??
                    null);

        return kandidat && /^[A-Za-z0-9_-]{11}$/.test(kandidat)
            ? kandidat
            : null;
    }

    if (!HOST_DRIVE.includes(host)) {
        return null;
    }

    const kandidat =
        u.pathname.match(/\/d\/([^/]+)/)?.[1] ??
        (['/open', '/uc'].includes(u.pathname)
            ? u.searchParams.get('id')
            : null);

    return kandidat && /^[A-Za-z0-9_-]{10,}$/.test(kandidat) ? kandidat : null;
}

export function urlEmbed(jenis: JenisMedia, id: string): string {
    return jenis === 'youtube'
        ? `https://www.youtube-nocookie.com/embed/${id}`
        : `https://drive.google.com/file/d/${id}/preview`;
}

export function urlBuka(jenis: JenisMedia, id: string): string {
    return jenis === 'youtube'
        ? `https://www.youtube.com/watch?v=${id}`
        : `https://drive.google.com/file/d/${id}/view`;
}
