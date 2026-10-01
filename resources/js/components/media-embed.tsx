import { ExternalLink, FileText, Image, PlayCircle } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

export type JenisMedia = 'youtube' | 'gambar' | 'dokumen';

export type Media = {
    jenis: JenisMedia;
    url: string;
    keterangan: string | null;
    url_embed: string;
    url_buka: string;
};

const ikon = { youtube: PlayCircle, gambar: Image, dokumen: FileText };

/**
 * Media materi berbasis link (CLAUDE.md bagian 6.4.1).
 * YouTube: iframe 16:9. Google Drive: iframe pratinjau + tombol "Buka di Google Drive".
 */
export function MediaEmbed({
    media,
    className,
}: {
    media: Media;
    className?: string;
}) {
    const Ikon = ikon[media.jenis];
    const youtube = media.jenis === 'youtube';

    return (
        <figure
            className={cn(
                'overflow-hidden rounded-2xl border bg-card shadow-xs',
                className,
            )}
        >
            <div
                className={cn(
                    'relative w-full bg-navy-50',
                    youtube || media.jenis === 'gambar'
                        ? 'aspect-video'
                        : 'aspect-[4/5] sm:aspect-[4/3]',
                )}
            >
                <iframe
                    src={media.url_embed}
                    title={media.keterangan ?? 'Media materi'}
                    className="absolute inset-0 size-full"
                    loading="lazy"
                    allow="accelerometer; encrypted-media; gyroscope; picture-in-picture; fullscreen"
                    allowFullScreen
                    referrerPolicy="strict-origin-when-cross-origin"
                />
            </div>
            <figcaption className="flex items-center gap-3 px-4 py-3">
                <Ikon className="size-4 shrink-0 text-navy-500" />
                <span className="min-w-0 flex-1 truncate text-sm font-medium text-navy-800">
                    {media.keterangan ??
                        (youtube ? 'Video YouTube' : 'File Google Drive')}
                </span>
                {!youtube && (
                    <Button asChild variant="outline" size="sm">
                        <a
                            href={media.url_buka}
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <ExternalLink />
                            <span className="hidden sm:inline">
                                Buka di Google Drive
                            </span>
                            <span className="sm:hidden">Buka</span>
                        </a>
                    </Button>
                )}
            </figcaption>
        </figure>
    );
}
