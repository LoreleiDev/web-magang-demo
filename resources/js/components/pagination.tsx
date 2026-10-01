import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { cn } from '@/lib/utils';
import type { Paginated } from '@/types';

/**
 * Navigasi halaman untuk paginator Laravel. Di smartphone hanya tombol
 * sebelumnya/berikutnya + posisi halaman; nomor halaman tampil di layar lebar.
 */
export function Pagination({
    data,
    label = 'data',
}: {
    data: Paginated<unknown>;
    label?: string;
}) {
    if (data.total === 0) {
        return null;
    }

    const nomor = data.links.slice(1, -1);

    return (
        <nav
            aria-label="Halaman"
            className="mt-6 flex flex-wrap items-center justify-between gap-3"
        >
            <p className="text-sm text-muted-foreground">
                {data.from}–{data.to} dari {data.total} {label}
            </p>

            {data.last_page > 1 && (
                <div className="flex items-center gap-1">
                    <TombolHalaman
                        url={data.prev_page_url}
                        aria-label="Halaman sebelumnya"
                    >
                        <ChevronLeft className="size-4" />
                    </TombolHalaman>

                    <span className="px-2 text-sm font-semibold text-navy-800 sm:hidden">
                        {data.current_page} / {data.last_page}
                    </span>

                    <div className="hidden items-center gap-1 sm:flex">
                        {nomor.map((link, i) =>
                            link.url === null ? (
                                <span
                                    key={`jeda-${i}`}
                                    className="px-2 text-sm text-muted-foreground"
                                >
                                    …
                                </span>
                            ) : (
                                <TombolHalaman
                                    key={link.url}
                                    url={link.url}
                                    aktif={link.active}
                                >
                                    {link.label}
                                </TombolHalaman>
                            ),
                        )}
                    </div>

                    <TombolHalaman
                        url={data.next_page_url}
                        aria-label="Halaman berikutnya"
                    >
                        <ChevronRight className="size-4" />
                    </TombolHalaman>
                </div>
            )}
        </nav>
    );
}

function TombolHalaman({
    url,
    aktif,
    children,
    ...props
}: {
    url: string | null;
    aktif?: boolean;
    children: React.ReactNode;
    'aria-label'?: string;
}) {
    const kelas = cn(
        'grid h-9 min-w-9 place-items-center rounded-lg px-2 text-sm font-semibold transition-colors',
        aktif
            ? 'bg-navy-900 text-white'
            : 'border bg-card text-navy-800 hover:bg-navy-50',
    );

    if (url === null) {
        return (
            <span
                className={cn(kelas, 'pointer-events-none opacity-40')}
                {...props}
            >
                {children}
            </span>
        );
    }

    return (
        <Link
            href={url}
            preserveScroll
            className={kelas}
            aria-current={aktif ? 'page' : undefined}
            {...props}
        >
            {children}
        </Link>
    );
}
