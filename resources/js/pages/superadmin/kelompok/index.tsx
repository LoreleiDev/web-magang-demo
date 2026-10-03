import { Head, Link, router } from '@inertiajs/react';
import {
    Building2,
    CalendarRange,
    GraduationCap,
    Pencil,
    Plus,
    Trash2,
    TriangleAlert,
    UsersRound,
} from 'lucide-react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { EmptyState } from '@/components/empty-state';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { formatPeriode } from '@/lib/format';
import { cn } from '@/lib/utils';
import kelompokRoutes from '@/routes/superadmin/kelompok';

type Kelompok = {
    id: number;
    nama_kelompok: string;
    perusahaan: string;
    guru: string | null;
    guru_aktif: boolean | null;
    periode_mulai: string;
    periode_selesai: string;
    status_periode: 'akan_datang' | 'berjalan' | 'selesai';
    jumlah_siswa: number;
};

const statusPeriode = {
    berjalan: { label: 'Berjalan', kelas: 'bg-hijau-50 text-hijau-700' },
    akan_datang: { label: 'Akan datang', kelas: 'bg-navy-50 text-navy-700' },
    selesai: { label: 'Selesai', kelas: 'bg-navy-100/70 text-navy-500' },
};

export default function KelompokIndex({
    kelompok,
    siswaTanpaKelompok,
}: {
    kelompok: Kelompok[];
    siswaTanpaKelompok: number;
}) {
    return (
        <>
            <Head title="Kelompok Magang" />
            <PageHeader
                eyebrow="Panel Superadmin"
                title="Kelompok magang"
                description="Satu kelompok = satu perusahaan, satu guru pembimbing, dan satu periode magang sesuai proposal siswa."
                actions={
                    <Button asChild>
                        <Link href={kelompokRoutes.create().url}>
                            <Plus />
                            Buat kelompok
                        </Link>
                    </Button>
                }
            />

            {siswaTanpaKelompok > 0 && (
                <div className="mb-5 flex items-start gap-3 rounded-2xl border border-gap-sedang/30 bg-gap-sedang-soft px-4 py-3 text-sm text-navy-900">
                    <TriangleAlert className="mt-0.5 size-4 shrink-0 text-gap-sedang" />
                    <span>
                        <strong>{siswaTanpaKelompok} siswa aktif</strong> belum
                        masuk kelompok mana pun dan belum bisa memulai
                        pendampingan.
                    </span>
                </div>
            )}

            {kelompok.length === 0 ? (
                <EmptyState
                    icon={UsersRound}
                    title="Belum ada kelompok magang"
                    description="Pastikan perusahaan dan akun guru sudah dibuat, lalu buat kelompok pertama."
                />
            ) : (
                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    {kelompok.map((k) => (
                        <article
                            key={k.id}
                            className="flex flex-col rounded-3xl border bg-card p-5 shadow-xs"
                        >
                            <div className="flex items-start justify-between gap-3">
                                <span
                                    className={cn(
                                        'rounded-full px-2.5 py-1 text-xs font-bold',
                                        statusPeriode[k.status_periode].kelas,
                                    )}
                                >
                                    {statusPeriode[k.status_periode].label}
                                </span>
                                <div className="-mt-1 -mr-1 flex gap-1">
                                    <Button
                                        asChild
                                        variant="ghost"
                                        size="icon-sm"
                                    >
                                        <Link
                                            href={kelompokRoutes.edit(k.id).url}
                                            aria-label={`Edit ${k.nama_kelompok}`}
                                        >
                                            <Pencil />
                                        </Link>
                                    </Button>
                                    <ConfirmDialog
                                        trigger={
                                            <Button
                                                variant="ghost"
                                                size="icon-sm"
                                                className="text-gap-tinggi hover:bg-gap-tinggi-soft hover:text-gap-tinggi"
                                                aria-label={`Hapus ${k.nama_kelompok}`}
                                            >
                                                <Trash2 />
                                            </Button>
                                        }
                                        title={`Hapus kelompok ${k.nama_kelompok}?`}
                                        description={
                                            k.jumlah_siswa > 0
                                                ? `${k.jumlah_siswa} siswa di kelompok ini akan menjadi belum masuk kelompok dan harus memilih unit kerja lagi setelah dimasukkan ke kelompok baru. Logbook dan hasil kuis mereka tetap tersimpan.`
                                                : 'Kelompok ini belum berisi siswa dan akan dihapus permanen.'
                                        }
                                        confirmLabel="Hapus kelompok"
                                        destructive
                                        onConfirm={() =>
                                            router.delete(
                                                kelompokRoutes.destroy(k.id)
                                                    .url,
                                                { preserveScroll: true },
                                            )
                                        }
                                    />
                                </div>
                            </div>
                            <h2 className="mt-3 text-base leading-snug font-bold text-navy-900">
                                {k.nama_kelompok}
                            </h2>
                            <dl className="mt-4 flex-1 space-y-2 text-sm">
                                <Info icon={Building2}>{k.perusahaan}</Info>
                                <Info icon={GraduationCap}>
                                    {k.guru ?? (
                                        <span className="rounded-full bg-gap-sedang-soft px-2 py-0.5 text-[11px] font-bold text-navy-900">
                                            Belum ada guru pembimbing
                                        </span>
                                    )}
                                    {k.guru !== null && !k.guru_aktif && (
                                        <span className="ml-1.5 rounded-full bg-gap-tinggi-soft px-2 py-0.5 text-[11px] font-bold text-gap-tinggi">
                                            Akun nonaktif
                                        </span>
                                    )}
                                </Info>
                                <Info icon={CalendarRange}>
                                    {formatPeriode(
                                        k.periode_mulai,
                                        k.periode_selesai,
                                    )}
                                </Info>
                            </dl>
                            <div className="mt-5 flex items-center gap-2 border-t pt-4 text-sm font-semibold text-navy-800">
                                <UsersRound className="size-4 text-navy-400" />
                                <span className="tabular-nums">
                                    {k.jumlah_siswa}
                                </span>{' '}
                                siswa
                            </div>
                        </article>
                    ))}
                </div>
            )}
        </>
    );
}

function Info({
    icon: Icon,
    children,
}: {
    icon: typeof Building2;
    children: React.ReactNode;
}) {
    return (
        <div className="flex items-start gap-2 text-navy-700">
            <Icon className="mt-0.5 size-4 shrink-0 text-navy-400" />
            <dd className="min-w-0">{children}</dd>
        </div>
    );
}
