import { Head, usePage } from '@inertiajs/react';
import { BadgeCheck, Building2, UsersRound } from 'lucide-react';
import { useState } from 'react';
import { EmptyState } from '@/components/empty-state';
import { KartuSiswa } from '@/components/kartu-siswa';
import { PageHeader } from '@/components/page-header';
import { cn } from '@/lib/utils';
import industri from '@/routes/industri';
import type { RingkasanSiswa } from '@/types/kompetensi';

type Props = {
    perusahaan: string | null;
    kelompok: { id: number; nama: string }[];
    siswa: (RingkasanSiswa & { kelompok_id: number | null })[];
    menungguVerifikasi: number;
};

/**
 * Dashboard pembimbing industri (CLAUDE.md bagian 8).
 */
export default function IndustriDashboard({
    perusahaan,
    kelompok,
    siswa,
    menungguVerifikasi,
}: Props) {
    const { auth } = usePage().props;
    const [filter, setFilter] = useState<number | null>(null);
    const tampil =
        filter === null ? siswa : siswa.filter((s) => s.kelompok_id === filter);

    return (
        <>
            <Head title="Dashboard Industri" />
            <PageHeader
                eyebrow="Dashboard Pembimbing Industri"
                title={`Halo, ${auth.user?.name}`}
                description={
                    <span className="flex items-center gap-1.5">
                        <Building2 className="size-4 shrink-0 text-navy-400" />
                        Siswa yang magang di {perusahaan}
                    </span>
                }
            />

            {menungguVerifikasi > 0 && (
                <div className="mb-5 flex items-start gap-3 rounded-2xl border border-navy-900/15 bg-card px-4 py-3 text-sm text-navy-900 shadow-xs">
                    <BadgeCheck className="mt-0.5 size-5 shrink-0 text-hijau-600" />
                    <span>
                        <strong>{menungguVerifikasi} kompetensi</strong>{' '}
                        menunggu verifikasi Anda. Buka siswa yang bersangkutan,
                        lalu tekan <strong>Verifikasi Kompetensi</strong>.
                    </span>
                </div>
            )}

            {kelompok.length > 1 && (
                <div className="-mx-4 mb-5 flex gap-1.5 overflow-x-auto px-4 md:mx-0 md:px-0">
                    {[{ id: null, nama: 'Semua kelompok' }, ...kelompok].map(
                        (k) => (
                            <button
                                key={k.id ?? 'semua'}
                                type="button"
                                onClick={() => setFilter(k.id)}
                                className={cn(
                                    'h-9 shrink-0 rounded-full px-4 text-sm font-semibold transition-colors',
                                    filter === k.id
                                        ? 'bg-navy-900 text-white'
                                        : 'border bg-card text-navy-700 hover:bg-navy-50',
                                )}
                            >
                                {k.nama}
                            </button>
                        ),
                    )}
                </div>
            )}

            {tampil.length === 0 ? (
                <EmptyState
                    icon={UsersRound}
                    title="Belum ada siswa magang"
                    description="Siswa akan muncul setelah sekolah memasukkannya ke kelompok magang di perusahaan Anda."
                />
            ) : (
                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    {tampil.map((s) => (
                        <KartuSiswa
                            key={s.id}
                            siswa={s}
                            href={industri.siswa.show(s.id).url}
                        />
                    ))}
                </div>
            )}
        </>
    );
}
