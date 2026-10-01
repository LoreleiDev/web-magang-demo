import { Head } from '@inertiajs/react';
import { ClipboardCheck } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import { FilterSiswa } from '@/components/filter-siswa';
import { PageHeader } from '@/components/page-header';
import { Pagination } from '@/components/pagination';
import { useFilter } from '@/hooks/use-filter';
import { formatTanggalWaktu } from '@/lib/format';
import { cn } from '@/lib/utils';
import superadmin from '@/routes/superadmin';
import type { Paginated } from '@/types';

type Hasil = {
    id: number;
    tanggal: string;
    siswa: string;
    kelompok: string | null;
    materi: string;
    skor: number;
    lulus: boolean;
};

export default function PemantauanAssessment({
    hasil,
    filter,
    kelompok,
}: {
    hasil: Paginated<Hasil>;
    filter: { kelompok: number | null; cari: string };
    kelompok: { id: number; nama: string }[];
}) {
    const [nilai, ubah] = useFilter(superadmin.assessment.index().url, filter);

    return (
        <>
            <Head title="Assessment" />
            <PageHeader
                eyebrow="Pemantauan · baca-saja"
                title="Hasil assessment"
                description="Semua percobaan kuis siswa. Batas lulus 75."
            />

            <FilterSiswa kelompok={kelompok} nilai={nilai} ubah={ubah} />

            {hasil.data.length === 0 ? (
                <EmptyState
                    icon={ClipboardCheck}
                    title="Belum ada hasil assessment"
                    description="Hasil muncul setelah siswa mengerjakan kuis."
                />
            ) : (
                <ul className="overflow-hidden rounded-3xl border bg-card shadow-xs">
                    {hasil.data.map((h) => (
                        <li
                            key={h.id}
                            className="flex items-center gap-4 border-b px-5 py-4 last:border-none"
                        >
                            <div className="min-w-0 flex-1">
                                <div className="truncate text-sm font-bold text-navy-900">
                                    {h.siswa}
                                </div>
                                <div className="truncate text-sm text-navy-700">
                                    {h.materi}
                                </div>
                                <div className="text-xs text-muted-foreground">
                                    {h.kelompok ?? 'Tanpa kelompok'} ·{' '}
                                    {formatTanggalWaktu(h.tanggal)}
                                </div>
                            </div>
                            <div className="w-24 shrink-0 text-right sm:w-36">
                                <div className="flex items-baseline justify-end gap-2">
                                    <span className="text-xl font-extrabold text-navy-900 tabular-nums">
                                        {h.skor}
                                    </span>
                                    <span
                                        className={cn(
                                            'rounded-full px-2 py-0.5 text-[11px] font-bold',
                                            h.lulus
                                                ? 'bg-hijau-50 text-hijau-700'
                                                : 'bg-gap-tinggi-soft text-gap-tinggi',
                                        )}
                                    >
                                        {h.lulus ? 'Lulus' : 'Belum lulus'}
                                    </span>
                                </div>
                                <div className="mt-2 h-1.5 overflow-hidden rounded-full bg-navy-100">
                                    <div
                                        className={cn(
                                            'h-full rounded-full',
                                            h.lulus
                                                ? 'bg-hijau-500'
                                                : 'bg-gap-tinggi',
                                        )}
                                        style={{ width: `${h.skor}%` }}
                                    />
                                </div>
                            </div>
                        </li>
                    ))}
                </ul>
            )}

            <Pagination data={hasil} label="hasil" />
        </>
    );
}
