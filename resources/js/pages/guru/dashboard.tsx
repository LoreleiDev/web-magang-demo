import { Head, Link, usePage } from '@inertiajs/react';
import { Building2, CalendarRange, UsersRound } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import { KartuSiswa } from '@/components/kartu-siswa';
import { PageHeader } from '@/components/page-header';
import { formatPeriode } from '@/lib/format';
import { cn } from '@/lib/utils';
import guru from '@/routes/guru';
import type { RingkasanSiswa } from '@/types/kompetensi';

type Kelompok = {
    id: number;
    nama_kelompok: string;
    perusahaan: string;
    periode_mulai: string;
    periode_selesai: string;
    jumlah_siswa: number;
};

type Props = {
    kelompok: Kelompok[];
    kelompokTerpilih: number | null;
    siswa: RingkasanSiswa[];
    programGuru: string | null;
};

export default function GuruDashboard({
    kelompok,
    kelompokTerpilih,
    siswa,
}: Props) {
    const { auth } = usePage().props;
    const aktif = kelompok.find((k) => k.id === kelompokTerpilih);

    return (
        <>
            <Head title="Dashboard Guru" />
            <PageHeader
                eyebrow="Dashboard Guru Pembimbing"
                title={`Halo, ${auth.user?.name}`}
                description="Pantau siswa di kelompok magang yang Anda bimbing dan isi level kompetensinya."
            />

            {kelompok.length === 0 ? (
                <EmptyState
                    icon={UsersRound}
                    title="Belum ada kelompok bimbingan"
                    description="Anda belum ditetapkan sebagai guru pembimbing kelompok magang. Hubungi admin sekolah."
                />
            ) : (
                <>
                    <div
                        role="tablist"
                        aria-label="Kelompok magang"
                        className="-mx-4 mb-5 flex gap-2 overflow-x-auto px-4 pb-1 md:mx-0 md:px-0"
                    >
                        {kelompok.map((k) => {
                            const dipilih = k.id === kelompokTerpilih;

                            return (
                                <Link
                                    key={k.id}
                                    role="tab"
                                    aria-selected={dipilih}
                                    href={
                                        guru.dashboard({
                                            query: { kelompok: k.id },
                                        }).url
                                    }
                                    preserveScroll
                                    className={cn(
                                        'flex min-w-56 shrink-0 flex-col rounded-2xl border px-4 py-3 text-left transition-colors',
                                        dipilih
                                            ? 'border-navy-900 bg-navy-900 text-white shadow-md'
                                            : 'bg-card text-navy-900 hover:bg-navy-50',
                                    )}
                                >
                                    <span className="text-sm font-bold">
                                        {k.nama_kelompok}
                                    </span>
                                    <span
                                        className={cn(
                                            'text-xs',
                                            dipilih
                                                ? 'text-navy-200'
                                                : 'text-muted-foreground',
                                        )}
                                    >
                                        {k.jumlah_siswa} siswa
                                    </span>
                                </Link>
                            );
                        })}
                    </div>

                    {aktif && (
                        <div className="mb-5 flex flex-wrap gap-x-5 gap-y-1 text-sm text-navy-700">
                            <span className="flex items-center gap-1.5">
                                <Building2 className="size-4 text-navy-400" />
                                {aktif.perusahaan}
                            </span>
                            <span className="flex items-center gap-1.5">
                                <CalendarRange className="size-4 text-navy-400" />
                                {formatPeriode(
                                    aktif.periode_mulai,
                                    aktif.periode_selesai,
                                )}
                            </span>
                        </div>
                    )}

                    {siswa.length === 0 ? (
                        <EmptyState
                            icon={UsersRound}
                            title="Belum ada siswa di kelompok ini"
                        />
                    ) : (
                        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                            {siswa.map((s) => (
                                <KartuSiswa
                                    key={s.id}
                                    siswa={s}
                                    href={guru.siswa.show(s.id).url}
                                />
                            ))}
                        </div>
                    )}
                </>
            )}
        </>
    );
}
