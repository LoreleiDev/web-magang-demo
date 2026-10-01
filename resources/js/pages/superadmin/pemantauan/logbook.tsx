import { Head } from '@inertiajs/react';
import { NotebookPen } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import { FilterSiswa } from '@/components/filter-siswa';
import { LogbookKartu } from '@/components/logbook-kartu';
import { PageHeader } from '@/components/page-header';
import { Pagination } from '@/components/pagination';
import { useFilter } from '@/hooks/use-filter';
import superadmin from '@/routes/superadmin';
import type { Paginated } from '@/types';
import type { Logbook } from '@/types/kompetensi';

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
                            <LogbookKartu logbook={l} />
                        </li>
                    ))}
                </ul>
            )}

            <Pagination data={logbook} label="logbook" />
        </>
    );
}
