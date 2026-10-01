import { Head } from '@inertiajs/react';
import { ChevronDown, NotebookPen, Paperclip, Sparkles } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import { FilterSiswa } from '@/components/filter-siswa';
import { PageHeader } from '@/components/page-header';
import { Pagination } from '@/components/pagination';
import { useFilter } from '@/hooks/use-filter';
import { formatTanggalPanjang } from '@/lib/format';
import superadmin from '@/routes/superadmin';
import type { Paginated } from '@/types';

type Logbook = {
    id: number;
    tanggal: string;
    siswa: string;
    kelompok: string | null;
    aktivitas: string;
    peralatan_software: string | null;
    sudah_dipahami: string | null;
    baru_ditemui: string | null;
    kesulitan: string | null;
    pengetahuan_sekolah_digunakan: string | null;
    ingin_dipelajari: string | null;
    ada_bukti: boolean;
    sudah_dianalisis: boolean;
};

const isian: { kunci: keyof Logbook; label: string }[] = [
    { kunci: 'peralatan_software', label: 'Peralatan/software' },
    { kunci: 'sudah_dipahami', label: 'Sudah dipahami' },
    { kunci: 'baru_ditemui', label: 'Baru ditemui' },
    { kunci: 'kesulitan', label: 'Kesulitan' },
    {
        kunci: 'pengetahuan_sekolah_digunakan',
        label: 'Pengetahuan sekolah yang digunakan',
    },
    { kunci: 'ingin_dipelajari', label: 'Ingin dipelajari' },
];

export default function PemantauanLogbook({
    logbook,
    filter,
    kelompok,
}: {
    logbook: Paginated<Logbook>;
    filter: { kelompok: number | null; cari: string };
    kelompok: { id: number; nama: string }[];
}) {
    const [nilai, ubah] = useFilter(superadmin.logbook.index().url, filter);

    return (
        <>
            <Head title="Logbook" />
            <PageHeader
                eyebrow="Pemantauan · baca-saja"
                title="Logbook siswa"
                description="Catatan harian seluruh siswa magang."
            />

            <FilterSiswa kelompok={kelompok} nilai={nilai} ubah={ubah} />

            {logbook.data.length === 0 ? (
                <EmptyState
                    icon={NotebookPen}
                    title="Belum ada logbook"
                    description="Logbook muncul setelah siswa mengisinya."
                />
            ) : (
                <ul className="space-y-3">
                    {logbook.data.map((l) => (
                        <li key={l.id}>
                            <details className="group rounded-3xl border bg-card shadow-xs open:shadow-md">
                                <summary className="flex cursor-pointer list-none items-start gap-4 p-5 [&::-webkit-details-marker]:hidden">
                                    <div className="min-w-0 flex-1">
                                        <div className="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs">
                                            <span className="font-bold text-navy-900">
                                                {l.siswa}
                                            </span>
                                            {l.kelompok && (
                                                <span className="text-muted-foreground">
                                                    · {l.kelompok}
                                                </span>
                                            )}
                                        </div>
                                        <p className="mt-0.5 text-xs font-semibold text-hijau-600">
                                            {formatTanggalPanjang(l.tanggal)}
                                        </p>
                                        <p className="mt-2 line-clamp-2 text-sm text-navy-900 group-open:line-clamp-none">
                                            {l.aktivitas}
                                        </p>
                                        <div className="mt-2 flex gap-3 text-xs text-navy-500">
                                            {l.ada_bukti && (
                                                <span className="flex items-center gap-1">
                                                    <Paperclip className="size-3.5" />{' '}
                                                    Ada bukti
                                                </span>
                                            )}
                                            {l.sudah_dianalisis && (
                                                <span className="flex items-center gap-1">
                                                    <Sparkles className="size-3.5" />{' '}
                                                    Sudah dianalisis AI
                                                </span>
                                            )}
                                        </div>
                                    </div>
                                    <ChevronDown className="mt-1 size-5 shrink-0 text-navy-300 transition-transform group-open:rotate-180" />
                                </summary>
                                <dl className="grid gap-3 border-t px-5 py-4 sm:grid-cols-2">
                                    {isian.map(({ kunci, label }) => (
                                        <div
                                            key={kunci}
                                            className="rounded-2xl bg-navy-50/60 p-3"
                                        >
                                            <dt className="text-xs font-semibold text-navy-500">
                                                {label}
                                            </dt>
                                            <dd className="mt-1 text-sm whitespace-pre-line text-navy-900">
                                                {(l[kunci] as string | null) ??
                                                    '-'}
                                            </dd>
                                        </div>
                                    ))}
                                </dl>
                            </details>
                        </li>
                    ))}
                </ul>
            )}

            <Pagination data={logbook} label="logbook" />
        </>
    );
}
