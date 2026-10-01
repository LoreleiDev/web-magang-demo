import { FileText, Image, Info, PlayCircle, Trash2 } from 'lucide-react';
import type { JenisMedia } from '@/components/media-embed';
import { MediaEmbed } from '@/components/media-embed';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { ambilIdMedia, urlBuka, urlEmbed } from '@/lib/media-link';
import { cn } from '@/lib/utils';

export type MediaForm = { jenis: JenisMedia; url: string; keterangan: string };

const pilihanJenis: {
    jenis: JenisMedia;
    label: string;
    icon: typeof PlayCircle;
    contoh: string;
}[] = [
    {
        jenis: 'youtube',
        label: 'Video',
        icon: PlayCircle,
        contoh: 'https://www.youtube.com/watch?v=… atau https://youtu.be/…',
    },
    {
        jenis: 'gambar',
        label: 'Gambar',
        icon: Image,
        contoh: 'https://drive.google.com/file/d/…/view',
    },
    {
        jenis: 'dokumen',
        label: 'Dokumen',
        icon: FileText,
        contoh: 'https://drive.google.com/file/d/…/view',
    },
];

/**
 * Satu media berbasis link (CLAUDE.md bagian 6.4.1) dengan pratinjau langsung.
 */
export function EditorMedia({
    media,
    onChange,
    onHapus,
    errors,
}: {
    media: MediaForm;
    onChange: (media: MediaForm) => void;
    onHapus: () => void;
    errors: { url?: string; jenis?: string; keterangan?: string };
}) {
    const id = media.url ? ambilIdMedia(media.jenis, media.url) : null;
    const contoh = pilihanJenis.find((p) => p.jenis === media.jenis)?.contoh;
    const drive = media.jenis !== 'youtube';

    return (
        <div className="rounded-2xl border bg-card p-3 sm:p-4">
            <div className="flex items-start gap-2">
                <div className="grid flex-1 grid-cols-3 gap-1 rounded-xl bg-navy-50 p-1">
                    {pilihanJenis.map((p) => (
                        <button
                            key={p.jenis}
                            type="button"
                            onClick={() =>
                                onChange({ ...media, jenis: p.jenis })
                            }
                            aria-pressed={media.jenis === p.jenis}
                            className={cn(
                                'flex h-9 items-center justify-center gap-1.5 rounded-lg px-1 text-xs font-semibold transition-colors',
                                media.jenis === p.jenis
                                    ? 'bg-card text-navy-900 shadow-xs'
                                    : 'text-navy-500 hover:text-navy-800',
                            )}
                        >
                            <p.icon className="size-4 shrink-0" />
                            <span className="truncate">{p.label}</span>
                        </button>
                    ))}
                </div>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    onClick={onHapus}
                    aria-label="Hapus media"
                >
                    <Trash2 />
                </Button>
            </div>

            <div className="mt-3 grid gap-3 sm:grid-cols-[1fr_14rem]">
                <div>
                    <Input
                        value={media.url}
                        onChange={(e) =>
                            onChange({ ...media, url: e.target.value })
                        }
                        placeholder={contoh}
                        inputMode="url"
                        aria-label="Link media"
                        aria-invalid={!!errors.url}
                    />
                    {errors.url && (
                        <p className="mt-1 text-sm font-medium text-destructive">
                            {errors.url}
                        </p>
                    )}
                    {!errors.url && media.url && id === null && (
                        <p className="mt-1 text-sm font-medium text-gap-sedang">
                            Link belum dikenali.{' '}
                            {drive
                                ? 'Gunakan link Google Drive/Docs.'
                                : 'Gunakan link YouTube.'}
                        </p>
                    )}
                </div>
                <Input
                    value={media.keterangan}
                    onChange={(e) =>
                        onChange({ ...media, keterangan: e.target.value })
                    }
                    placeholder="Keterangan (opsional)"
                    aria-label="Keterangan media"
                />
            </div>

            {drive && (
                <p className="mt-3 flex gap-2 rounded-xl bg-gap-sedang-soft px-3 py-2 text-xs leading-relaxed text-navy-900">
                    <Info className="mt-0.5 size-4 shrink-0 text-gap-sedang" />
                    <span>
                        Atur file di Google Drive menjadi{' '}
                        <strong>
                            "Siapa saja yang memiliki link dapat melihat"
                        </strong>
                        . Jika tidak, media tidak akan tampil untuk siswa.
                    </span>
                </p>
            )}

            {id && (
                <div className="mt-3">
                    <p className="mb-2 text-xs font-semibold text-navy-500">
                        Pratinjau
                    </p>
                    <MediaEmbed
                        media={{
                            jenis: media.jenis,
                            url: media.url,
                            keterangan: media.keterangan || null,
                            url_embed: urlEmbed(media.jenis, id),
                            url_buka: urlBuka(media.jenis, id),
                        }}
                        className="max-w-xl"
                    />
                </div>
            )}
        </div>
    );
}
