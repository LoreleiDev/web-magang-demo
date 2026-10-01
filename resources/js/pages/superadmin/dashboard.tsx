import { Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    BadgeCheck,
    BookOpen,
    Building2,
    GraduationCap,
    UserPlus,
    UsersRound,
} from 'lucide-react';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import superadmin from '@/routes/superadmin';

type Props = {
    statistik: {
        guru: number;
        siswa: number;
        industri: number;
        perusahaan: number;
        kelompok_berjalan: number;
        kelompok_total: number;
        materi: number;
        materi_terverifikasi: number;
    };
    siswaTanpaKelompok: {
        id: number;
        nama: string;
        id_siswa: string | null;
        program_keahlian: string | null;
    }[];
    jumlahSiswaTanpaKelompok: number;
};

export default function SuperadminDashboard({
    statistik,
    siswaTanpaKelompok,
    jumlahSiswaTanpaKelompok,
}: Props) {
    const { auth } = usePage().props;

    return (
        <>
            <Head title="Panel Superadmin" />
            <PageHeader
                eyebrow="Panel Superadmin"
                title={`Halo, ${auth.user?.name}`}
                description="Kelola akun, data perusahaan, dan kelompok magang."
                actions={
                    <Button asChild>
                        <Link href={superadmin.akun.create().url}>
                            <UserPlus />
                            Tambah akun
                        </Link>
                    </Button>
                }
            />

            <div className="grid gap-4 lg:grid-cols-3">
                <section className="relative overflow-hidden rounded-3xl bg-navy-900 tekstur-titik p-6 text-white shadow-lg lg:col-span-2 lg:p-8">
                    <p className="text-xs font-bold tracking-[0.16em] text-hijau-500 uppercase">
                        Magang berjalan
                    </p>
                    <div className="mt-4 flex flex-wrap items-end gap-x-10 gap-y-5">
                        <div>
                            <div className="text-5xl font-extrabold tracking-tight tabular-nums">
                                {statistik.kelompok_berjalan}
                            </div>
                            <div className="mt-1 text-sm text-navy-200">
                                kelompok aktif dari {statistik.kelompok_total}{' '}
                                kelompok
                            </div>
                        </div>
                        <div>
                            <div className="text-5xl font-extrabold tracking-tight tabular-nums">
                                {statistik.siswa}
                            </div>
                            <div className="mt-1 text-sm text-navy-200">
                                siswa dengan akun aktif
                            </div>
                        </div>
                    </div>
                    <Link
                        href={superadmin.kelompok.index().url}
                        className="mt-8 inline-flex items-center gap-2 text-sm font-semibold text-white hover:text-hijau-500"
                    >
                        Lihat kelompok magang <ArrowRight className="size-4" />
                    </Link>
                </section>

                <div className="grid grid-cols-2 gap-4 lg:grid-cols-1">
                    <Angka
                        icon={GraduationCap}
                        nilai={statistik.guru}
                        label="Guru pembimbing"
                    />
                    <Angka
                        icon={Building2}
                        nilai={statistik.perusahaan}
                        label={`Perusahaan · ${statistik.industri} akun industri`}
                    />
                </div>
            </div>

            <div className="mt-4 grid gap-4 lg:grid-cols-[1fr_1.4fr]">
                <section className="rounded-3xl border bg-card p-6 shadow-xs">
                    <div className="flex items-center gap-3">
                        <span className="grid size-10 place-items-center rounded-xl bg-navy-50 text-navy-700">
                            <BookOpen className="size-5" />
                        </span>
                        <div>
                            <div className="text-2xl font-extrabold text-navy-900 tabular-nums">
                                {statistik.materi}
                            </div>
                            <div className="text-sm text-muted-foreground">
                                materi dibuat guru
                            </div>
                        </div>
                    </div>
                    <div className="mt-5 flex items-center gap-2 rounded-xl bg-hijau-50 px-3 py-2.5 text-sm font-semibold text-hijau-700">
                        <BadgeCheck className="size-4" />
                        {statistik.materi_terverifikasi} diverifikasi oleh
                        industri
                    </div>
                    <Link
                        href={superadmin.materi.index().url}
                        className="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-navy-700 hover:text-navy-900"
                    >
                        Lihat materi <ArrowRight className="size-4" />
                    </Link>
                </section>

                <section className="rounded-3xl border bg-card p-6 shadow-xs">
                    <div className="flex items-start justify-between gap-4">
                        <div>
                            <h2 className="text-base font-bold text-navy-900">
                                Siswa belum masuk kelompok
                            </h2>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Siswa ini belum bisa memulai pendampingan.
                            </p>
                        </div>
                        <span className="rounded-full bg-gap-sedang-soft px-2.5 py-1 text-sm font-bold text-navy-900 tabular-nums">
                            {jumlahSiswaTanpaKelompok}
                        </span>
                    </div>

                    {siswaTanpaKelompok.length === 0 ? (
                        <p className="mt-6 flex items-center gap-2 text-sm font-medium text-hijau-700">
                            <BadgeCheck className="size-4" />
                            Semua siswa aktif sudah masuk kelompok.
                        </p>
                    ) : (
                        <>
                            <ul className="mt-4 divide-y">
                                {siswaTanpaKelompok.map((s) => (
                                    <li
                                        key={s.id}
                                        className="flex items-center justify-between gap-3 py-2.5"
                                    >
                                        <span className="truncate text-sm font-semibold text-navy-900">
                                            {s.nama}
                                        </span>
                                        <span className="shrink-0 text-xs text-muted-foreground">
                                            {s.program_keahlian} · NIS{' '}
                                            {s.id_siswa}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                            <Button asChild variant="outline" className="mt-4">
                                <Link href={superadmin.kelompok.index().url}>
                                    <UsersRound />
                                    Atur kelompok
                                </Link>
                            </Button>
                        </>
                    )}
                </section>
            </div>
        </>
    );
}

function Angka({
    icon: Icon,
    nilai,
    label,
}: {
    icon: typeof GraduationCap;
    nilai: number;
    label: string;
}) {
    return (
        <div className="rounded-3xl border bg-card p-5 shadow-xs">
            <Icon className="size-5 text-navy-400" />
            <div className="mt-3 text-3xl font-extrabold text-navy-900 tabular-nums">
                {nilai}
            </div>
            <div className="mt-0.5 text-sm text-muted-foreground">{label}</div>
        </div>
    );
}
