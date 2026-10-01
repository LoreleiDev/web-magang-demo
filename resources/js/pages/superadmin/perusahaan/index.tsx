import { Head, Link, router } from '@inertiajs/react';
import {
    Building2,
    FileText,
    MapPin,
    Pencil,
    Plus,
    Trash2,
    UserRound,
    UsersRound,
} from 'lucide-react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { EmptyState } from '@/components/empty-state';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import perusahaanRoutes from '@/routes/superadmin/perusahaan';

type Perusahaan = {
    id: number;
    nama: string;
    alamat: string | null;
    bidang_usaha: string | null;
    daftar_unit_kerja: string[];
    jumlah_kelompok: number;
    jumlah_akun_industri: number;
    jumlah_dokumen: number;
    bisa_dihapus: boolean;
};

export default function PerusahaanIndex({
    perusahaan,
}: {
    perusahaan: Perusahaan[];
}) {
    return (
        <>
            <Head title="Perusahaan" />
            <PageHeader
                eyebrow="Panel Superadmin"
                title="Perusahaan"
                description="Tempat magang siswa. Nama perusahaan dan unit kerja tampil di halaman siswa; dokumen industri menjadi sumber jawaban AI Mentor."
                actions={
                    <Button asChild>
                        <Link href={perusahaanRoutes.create().url}>
                            <Plus />
                            Tambah perusahaan
                        </Link>
                    </Button>
                }
            />

            {perusahaan.length === 0 ? (
                <EmptyState
                    icon={Building2}
                    title="Belum ada perusahaan"
                    description="Tambahkan perusahaan sebelum membuat kelompok magang dan akun industri."
                />
            ) : (
                <div className="grid gap-4 md:grid-cols-2">
                    {perusahaan.map((p) => (
                        <KartuPerusahaan key={p.id} perusahaan={p} />
                    ))}
                </div>
            )}
        </>
    );
}

function KartuPerusahaan({ perusahaan: p }: { perusahaan: Perusahaan }) {
    const hapus = () =>
        router.delete(perusahaanRoutes.destroy(p.id).url, {
            preserveScroll: true,
        });

    return (
        <article className="flex flex-col rounded-3xl border bg-card p-2 shadow-xs">
            <div className="flex-1 p-4">
                <div className="flex items-start gap-3">
                    <span className="grid size-11 shrink-0 place-items-center rounded-xl bg-navy-900 text-white">
                        <Building2 className="size-5" />
                    </span>
                    <div className="min-w-0">
                        <h2 className="text-base leading-snug font-bold text-navy-900">
                            {p.nama}
                        </h2>
                        {p.bidang_usaha && (
                            <p className="text-sm text-muted-foreground">
                                {p.bidang_usaha}
                            </p>
                        )}
                    </div>
                </div>
                {p.alamat && (
                    <p className="mt-3 flex gap-2 text-sm text-navy-700">
                        <MapPin className="mt-0.5 size-4 shrink-0 text-navy-400" />
                        {p.alamat}
                    </p>
                )}
                <div className="mt-4 flex flex-wrap gap-1.5">
                    {p.daftar_unit_kerja.map((unit) => (
                        <span
                            key={unit}
                            className="rounded-full border bg-navy-50/60 px-2.5 py-1 text-xs font-medium text-navy-700"
                        >
                            {unit}
                        </span>
                    ))}
                </div>
            </div>

            <div className="flex flex-wrap items-center gap-x-4 gap-y-2 rounded-[20px] bg-navy-50/70 px-4 py-3">
                <Hitung
                    icon={UsersRound}
                    nilai={p.jumlah_kelompok}
                    label="kelompok"
                />
                <Hitung
                    icon={UserRound}
                    nilai={p.jumlah_akun_industri}
                    label="akun industri"
                />
                <Hitung
                    icon={FileText}
                    nilai={p.jumlah_dokumen}
                    label="dokumen"
                />

                <div className="ml-auto flex gap-1">
                    <Button asChild variant="ghost" size="icon-sm">
                        <Link
                            href={perusahaanRoutes.edit(p.id).url}
                            aria-label={`Edit ${p.nama}`}
                        >
                            <Pencil />
                        </Link>
                    </Button>
                    {p.bisa_dihapus ? (
                        <ConfirmDialog
                            trigger={
                                <Button
                                    variant="ghost"
                                    size="icon-sm"
                                    aria-label={`Hapus ${p.nama}`}
                                >
                                    <Trash2 />
                                </Button>
                            }
                            title={`Hapus ${p.nama}?`}
                            description="Data perusahaan dan semua dokumen industrinya akan dihapus permanen."
                            confirmLabel="Hapus"
                            destructive
                            onConfirm={hapus}
                        />
                    ) : (
                        <Tooltip>
                            <TooltipTrigger asChild>
                                <span tabIndex={0}>
                                    <Button
                                        variant="ghost"
                                        size="icon-sm"
                                        disabled
                                        aria-label="Tidak bisa dihapus"
                                    >
                                        <Trash2 />
                                    </Button>
                                </span>
                            </TooltipTrigger>
                            <TooltipContent>
                                Masih dipakai kelompok magang atau akun industri
                            </TooltipContent>
                        </Tooltip>
                    )}
                </div>
            </div>
        </article>
    );
}

function Hitung({
    icon: Icon,
    nilai,
    label,
}: {
    icon: typeof Building2;
    nilai: number;
    label: string;
}) {
    return (
        <span className="flex items-center gap-1.5 text-xs text-navy-700">
            <Icon className="size-3.5 text-navy-400" />
            <strong className="tabular-nums">{nilai}</strong> {label}
        </span>
    );
}
