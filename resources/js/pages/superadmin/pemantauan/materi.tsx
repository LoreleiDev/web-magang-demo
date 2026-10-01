import { Head, Link, usePage } from '@inertiajs/react';
import { BookOpen, ChevronRight } from 'lucide-react';
import { BadgeVerifikasi } from '@/components/badge-verifikasi';
import { EmptyState } from '@/components/empty-state';
import { PageHeader } from '@/components/page-header';
import { Pagination } from '@/components/pagination';
import { useFilter } from '@/hooks/use-filter';
import { formatTanggal } from '@/lib/format';
import { cn } from '@/lib/utils';
import superadmin from '@/routes/superadmin';
import type { Paginated } from '@/types';
import type { MateriRingkas } from '@/types/materi';

export default function PemantauanMateri({
    materi,
    filter,
}: {
    materi: Paginated<MateriRingkas>;
    filter: { program: string | null };
}) {
    const { programKeahlian } = usePage().props;
    const [nilai, ubah] = useFilter(superadmin.materi.index().url, {
        program: filter.program,
    });

    return (
        <>
            <Head title="Materi" />
            <PageHeader
                eyebrow="Pemantauan · baca-saja"
                title="Materi pembelajaran"
                description="Materi dibuat dan diedit oleh guru per program keahlian."
            />

            <div className="-mx-4 mb-5 flex gap-1.5 overflow-x-auto px-4 md:mx-0 md:px-0">
                {[null, ...Object.keys(programKeahlian)].map((p) => (
                    <button
                        key={p ?? 'semua'}
                        type="button"
                        onClick={() => ubah('program', p)}
                        className={cn(
                            'h-9 shrink-0 rounded-full px-4 text-sm font-semibold transition-colors',
                            nilai.program === p
                                ? 'bg-navy-900 text-white'
                                : 'border bg-card text-navy-700 hover:bg-navy-50',
                        )}
                    >
                        {p ?? 'Semua program'}
                    </button>
                ))}
            </div>

            {materi.data.length === 0 ? (
                <EmptyState
                    icon={BookOpen}
                    title="Belum ada materi"
                    description="Materi akan muncul setelah guru membuatnya."
                />
            ) : (
                <ul className="grid gap-3 md:grid-cols-2">
                    {materi.data.map((m) => (
                        <li key={m.id}>
                            <Link
                                href={superadmin.materi.show(m.id).url}
                                className="group flex h-full items-start gap-4 rounded-3xl border bg-card p-5 shadow-xs transition-shadow hover:shadow-md"
                            >
                                <span className="grid size-11 shrink-0 place-items-center rounded-xl bg-navy-50 text-sm font-extrabold text-navy-700">
                                    {m.program_keahlian}
                                </span>
                                <div className="min-w-0 flex-1">
                                    <h2 className="leading-snug font-bold text-navy-900">
                                        {m.judul}
                                    </h2>
                                    <BadgeVerifikasi
                                        terverifikasi={m.terverifikasi_industri}
                                        className="mt-2"
                                    />
                                    <p className="mt-2 text-sm text-muted-foreground">
                                        {m.kompetensi}
                                    </p>
                                    <p className="mt-1 text-xs text-navy-500">
                                        {m.pembuat} · diubah{' '}
                                        {formatTanggal(m.diubah_terakhir)}
                                    </p>
                                </div>
                                <ChevronRight className="mt-1 size-5 shrink-0 text-navy-300 transition-transform group-hover:translate-x-0.5" />
                            </Link>
                        </li>
                    ))}
                </ul>
            )}

            <Pagination data={materi} label="materi" />
        </>
    );
}
