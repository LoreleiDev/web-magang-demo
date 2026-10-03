import { Head, Link, router } from '@inertiajs/react';
import {
    ArrowDownUp,
    GraduationCap,
    Pencil,
    Power,
    PowerOff,
    Search,
    Trash2,
    TriangleAlert,
    UserPlus,
    Users,
} from 'lucide-react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { EmptyState } from '@/components/empty-state';
import { PageHeader } from '@/components/page-header';
import { Pagination } from '@/components/pagination';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { UserAvatar } from '@/components/user-menu';
import { useFilter } from '@/hooks/use-filter';
import { cn } from '@/lib/utils';
import akunRoutes from '@/routes/superadmin/akun';
import type { Paginated, Role } from '@/types';

/** Dampak hapus akun per role (revisi keputusan 25). */
const dampakHapus: Record<Role, string> = {
    siswa: 'Semua data siswa ini ikut terhapus permanen: logbook beserta bukti kegiatan, hasil kuis, progres kompetensi, dan chat AI Mentor. Tindakan ini tidak bisa dibatalkan.',
    guru: 'Akun guru dihapus permanen. Kompetensi, materi, dan dokumen sekolah buatannya tetap ada. Kelompok yang ia bimbing menjadi tanpa guru pembimbing sampai Anda menetapkan guru baru.',
    industri:
        'Akun industri dihapus permanen. Status kompetensi yang sudah diverifikasi dan riwayat verifikasi materi tetap tersimpan beserta nama pemeriksanya.',
    superadmin: 'Akun superadmin tidak bisa dihapus.',
};

type BarisAkun = {
    id: number;
    name: string;
    email: string;
    role: Role;
    role_label: string;
    status_aktif: boolean;
    keterangan: string[];
    perlu_perhatian: boolean;
};

type Props = {
    akun: Paginated<BarisAkun>;
    filter: {
        role: Role | null;
        cari: string;
        program: string | null;
        urut: Urutan;
    };
    opsiProgram: { kode: string; nama: string }[];
    jumlah: { semua: number; guru: number; siswa: number; industri: number };
};

type Urutan = 'nama' | 'nama_desc' | 'nomor' | 'terbaru';

const urutan: { nilai: Urutan; label: string }[] = [
    { nilai: 'nama', label: 'Nama A–Z' },
    { nilai: 'nama_desc', label: 'Nama Z–A' },
    { nilai: 'nomor', label: 'NIS / NIP' },
    { nilai: 'terbaru', label: 'Terbaru dibuat' },
];

const SEMUA = 'semua';

const tab = [
    { role: null, label: 'Semua', kunci: 'semua' },
    { role: 'guru', label: 'Guru', kunci: 'guru' },
    { role: 'siswa', label: 'Siswa', kunci: 'siswa' },
    { role: 'industri', label: 'Industri', kunci: 'industri' },
] as const;

export default function AkunIndex({
    akun,
    filter,
    opsiProgram,
    jumlah,
}: Props) {
    const [nilai, ubah] = useFilter(akunRoutes.index().url, {
        role: filter.role,
        cari: filter.cari,
        program: filter.program,
        urut: (filter.urut === 'nama' ? null : filter.urut) as Urutan | null,
    });

    return (
        <>
            <Head title="Akun" />
            <PageHeader
                eyebrow="Panel Superadmin"
                title="Akun pengguna"
                description="Buat akun login untuk guru, siswa, dan pembimbing industri. Nonaktifkan akun untuk menghentikan akses sementara, atau hapus jika tidak dipakai lagi."
                actions={
                    <Button asChild>
                        <Link
                            href={
                                akunRoutes.create({
                                    query: { role: filter.role ?? undefined },
                                }).url
                            }
                        >
                            <UserPlus />
                            Tambah akun
                        </Link>
                    </Button>
                }
            />

            <div className="mb-5 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div
                    role="tablist"
                    className="-mx-4 flex gap-1.5 overflow-x-auto px-4 md:mx-0 md:px-0"
                >
                    {tab.map((t) => {
                        const aktif = nilai.role === t.role;

                        return (
                            <button
                                key={t.kunci}
                                role="tab"
                                aria-selected={aktif}
                                onClick={() => ubah('role', t.role)}
                                className={cn(
                                    'flex h-9 shrink-0 items-center gap-2 rounded-full px-4 text-sm font-semibold transition-colors',
                                    aktif
                                        ? 'bg-navy-900 text-white'
                                        : 'border bg-card text-navy-700 hover:bg-navy-50',
                                )}
                            >
                                {t.label}
                                <span
                                    className={cn(
                                        'text-xs tabular-nums',
                                        aktif
                                            ? 'text-navy-200'
                                            : 'text-muted-foreground',
                                    )}
                                >
                                    {jumlah[t.kunci]}
                                </span>
                            </button>
                        );
                    })}
                </div>

                <div className="relative md:w-72">
                    <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        type="search"
                        value={nilai.cari}
                        onChange={(e) => ubah('cari', e.target.value)}
                        placeholder="Cari nama, email, NIS, atau NIP"
                        className="pl-9"
                        aria-label="Cari akun"
                    />
                </div>
            </div>

            <div className="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center">
                {/* Akun industri tidak punya program keahlian. */}
                {nilai.role !== 'industri' && (
                    <Select
                        value={nilai.program ?? SEMUA}
                        onValueChange={(v) =>
                            ubah('program', v === SEMUA ? null : v)
                        }
                    >
                        <SelectTrigger
                            className="w-full justify-start sm:w-72 [&>svg:last-child]:ml-auto"
                            aria-label="Program keahlian"
                        >
                            <GraduationCap className="text-navy-400" />
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={SEMUA}>
                                Semua program keahlian
                            </SelectItem>
                            {opsiProgram.map((p) => (
                                <SelectItem key={p.kode} value={p.kode}>
                                    {p.nama}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                )}
                <Select
                    value={nilai.urut ?? 'nama'}
                    onValueChange={(v) =>
                        ubah('urut', v === 'nama' ? null : (v as Urutan))
                    }
                >
                    <SelectTrigger
                        className="w-full justify-start sm:w-56 [&>svg:last-child]:ml-auto"
                        aria-label="Urutkan"
                    >
                        <ArrowDownUp className="text-navy-400" />
                        <span className="text-muted-foreground">Urutkan:</span>
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        {urutan.map((u) => (
                            <SelectItem key={u.nilai} value={u.nilai}>
                                {u.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>

            {akun.data.length === 0 ? (
                <EmptyState
                    icon={Users}
                    title="Tidak ada akun"
                    description={
                        filter.cari
                            ? 'Tidak ada akun yang cocok dengan pencarian.'
                            : 'Belum ada akun untuk kategori ini.'
                    }
                />
            ) : (
                <ul className="overflow-hidden rounded-3xl border bg-card shadow-xs">
                    {akun.data.map((a) => (
                        <BarisAkunItem key={a.id} akun={a} />
                    ))}
                </ul>
            )}

            <Pagination data={akun} label="akun" />
        </>
    );
}

function BarisAkunItem({ akun }: { akun: BarisAkun }) {
    const ubahStatus = () =>
        router.patch(
            akunRoutes.status(akun.id).url,
            {},
            { preserveScroll: true },
        );

    const hapus = () =>
        router.delete(akunRoutes.destroy(akun.id).url, {
            preserveScroll: true,
        });

    return (
        <li
            className={cn(
                'flex flex-col gap-3 border-b px-4 py-4 last:border-none sm:px-5 md:flex-row md:items-center md:gap-5',
                !akun.status_aktif && 'bg-navy-50/50',
            )}
        >
            <div className="flex min-w-0 flex-1 items-start gap-3">
                <UserAvatar
                    user={{ ...akun }}
                    className={cn(!akun.status_aktif && 'opacity-50')}
                />
                <div className="min-w-0">
                    <div className="flex flex-wrap items-center gap-x-2 gap-y-1">
                        <span
                            className={cn(
                                'font-semibold text-navy-900',
                                !akun.status_aktif && 'text-muted-foreground',
                            )}
                        >
                            {akun.name}
                        </span>
                        <span className="rounded-full bg-navy-50 px-2 py-0.5 text-[11px] font-bold text-navy-700">
                            {akun.role_label}
                        </span>
                        {!akun.status_aktif && (
                            <span className="rounded-full bg-navy-100 px-2 py-0.5 text-[11px] font-bold text-navy-600">
                                Nonaktif
                            </span>
                        )}
                    </div>
                    <div className="truncate text-sm text-muted-foreground">
                        {akun.email}
                    </div>
                    {akun.keterangan.length > 0 && (
                        <div className="mt-1.5 flex flex-wrap items-center gap-x-1.5 text-xs text-navy-600">
                            {akun.perlu_perhatian && (
                                <TriangleAlert className="size-3.5 text-gap-sedang" />
                            )}
                            {akun.keterangan.join(' · ')}
                        </div>
                    )}
                </div>
            </div>

            <div className="flex shrink-0 gap-2 pl-12 md:pl-0">
                <Button asChild variant="outline" size="sm">
                    <Link href={akunRoutes.edit(akun.id).url}>
                        <Pencil />
                        Edit
                    </Link>
                </Button>
                {akun.status_aktif ? (
                    <ConfirmDialog
                        trigger={
                            <Button variant="ghost" size="sm">
                                <PowerOff />
                                Nonaktifkan
                            </Button>
                        }
                        title={`Nonaktifkan akun ${akun.name}?`}
                        description="Pengguna ini tidak bisa login sampai akunnya diaktifkan kembali. Data yang sudah ada tetap tersimpan."
                        confirmLabel="Nonaktifkan"
                        destructive
                        onConfirm={ubahStatus}
                    />
                ) : (
                    <Button variant="ghost" size="sm" onClick={ubahStatus}>
                        <Power />
                        Aktifkan
                    </Button>
                )}
                <ConfirmDialog
                    trigger={
                        <Button
                            variant="ghost"
                            size="icon-sm"
                            className="text-gap-tinggi hover:bg-gap-tinggi-soft hover:text-gap-tinggi"
                            aria-label={`Hapus akun ${akun.name}`}
                        >
                            <Trash2 />
                        </Button>
                    }
                    title={`Hapus akun ${akun.name}?`}
                    description={dampakHapus[akun.role]}
                    confirmLabel="Hapus permanen"
                    destructive
                    onConfirm={hapus}
                />
            </div>
        </li>
    );
}
