import { Head, Link } from '@inertiajs/react';
import {
    ArrowRight,
    BookOpen,
    Building2,
    ClipboardCheck,
    NotebookPen,
    RefreshCw,
    Sparkles,
    Target,
} from 'lucide-react';
import { BadgeVerifikasi } from '@/components/badge-verifikasi';
import {
    GapBadge,
    LevelBar,
    namaLevel,
    StatusBadge,
} from '@/components/kompetensi-indikator';
import { Button } from '@/components/ui/button';
import { formatTanggalPanjang } from '@/lib/format';
import { cn } from '@/lib/utils';
import siswa from '@/routes/siswa';
import type { BarisKompetensi, RingkasanProgres } from '@/types/kompetensi';
import type { KonteksSiswa, Rekomendasi } from '@/types/siswa';

type Aktivitas = {
    jenis: 'logbook' | 'belajar' | 'kuis';
    judul: string;
    keterangan: string;
    materi_id?: number;
};

type Props = {
    konteks: KonteksSiswa;
    ringkasan: RingkasanProgres;
    fokus: BarisKompetensi | null;
    kompetensiGap: BarisKompetensi[];
    aktivitasHariIni: Aktivitas[];
    rekomendasi: Rekomendasi[];
    logbookTerakhir: {
        id: number;
        tanggal: string;
        aktivitas: string;
        kesulitan: string | null;
    } | null;
};

const ikonAktivitas = {
    logbook: NotebookPen,
    belajar: BookOpen,
    kuis: ClipboardCheck,
};

function hrefAktivitas(a: Aktivitas): string {
    if (a.jenis === 'logbook') {
        return siswa.logbook.index().url;
    }

    return siswa.belajar.show(a.materi_id ?? 0).url;
}

export default function SiswaDashboard({
    konteks,
    ringkasan,
    fokus,
    kompetensiGap,
    aktivitasHariIni,
    rekomendasi,
    logbookTerakhir,
}: Props) {
    return (
        <>
            <Head title="Dashboard" />

            <header className="mb-6">
                <p className="text-xs font-bold tracking-[0.16em] text-hijau-600 uppercase">
                    Pendampingan magang
                </p>
                <h1 className="mt-1.5 text-2xl font-extrabold tracking-tight text-balance text-navy-900 sm:text-3xl">
                    Selamat datang, {konteks.nama}
                </h1>
                <p className="mt-2 flex items-start gap-2 text-sm text-muted-foreground">
                    <Building2 className="mt-0.5 size-4 shrink-0 text-navy-400" />
                    <span>
                        {konteks.perusahaan} · {konteks.unit_kerja}
                    </span>
                </p>
            </header>

            {/* Hari magang + progres */}
            <section className="grid gap-4 rounded-3xl bg-navy-900 tekstur-titik p-5 text-white shadow-lg sm:grid-cols-2 sm:p-7">
                <div>
                    <p className="text-xs font-semibold text-navy-300">
                        Pelaksanaan magang
                    </p>
                    {konteks.status_magang === 'berjalan' ? (
                        <>
                            <p className="mt-1 text-4xl font-extrabold tracking-tight tabular-nums">
                                Hari ke-{konteks.hari_ke}
                            </p>
                            <p className="text-sm text-navy-300">
                                dari {konteks.total_hari} hari magang
                            </p>
                            <div className="mt-3 h-1.5 overflow-hidden rounded-full bg-white/10">
                                <div
                                    className="h-full rounded-full bg-navy-200"
                                    style={{
                                        width: `${Math.min(100, ((konteks.hari_ke ?? 0) / (konteks.total_hari ?? 1)) * 100)}%`,
                                    }}
                                />
                            </div>
                        </>
                    ) : konteks.status_magang === 'belum_mulai' ? (
                        <p className="mt-1 text-2xl font-extrabold">
                            Dimulai {konteks.hari_menuju_mulai} hari lagi
                        </p>
                    ) : (
                        <p className="mt-1 text-2xl font-extrabold">
                            Masa magang telah selesai
                        </p>
                    )}
                </div>
                <div className="sm:border-l sm:border-white/10 sm:pl-6">
                    <p className="text-xs font-semibold text-navy-300">
                        Progres kompetensi
                    </p>
                    <p className="mt-1 text-4xl font-extrabold tracking-tight tabular-nums">
                        {ringkasan.persen}%
                    </p>
                    <p className="text-sm text-navy-300">
                        {ringkasan.dikuasai} dari {ringkasan.total} kompetensi
                        sudah dikuasai · {ringkasan.gap} masih gap
                    </p>
                    <div className="mt-3 h-1.5 overflow-hidden rounded-full bg-white/10">
                        <div
                            className="h-full rounded-full bg-hijau-500"
                            style={{ width: `${ringkasan.persen}%` }}
                        />
                    </div>
                </div>
            </section>

            <div className="mt-4 grid gap-4 lg:grid-cols-[1.15fr_1fr]">
                {/* Fokus + aktivitas hari ini */}
                <div className="min-w-0 space-y-4">
                    {fokus && (
                        <section className="rounded-3xl border bg-card p-5 shadow-xs">
                            <div className="flex items-center justify-between gap-3">
                                <p className="flex items-center gap-1.5 text-xs font-bold text-navy-500">
                                    <Target className="size-4" /> Kompetensi
                                    yang Anda jalani
                                </p>
                                <Link
                                    href={siswa.mulai().url}
                                    className="flex items-center gap-1 text-xs font-semibold text-navy-600 hover:text-navy-900"
                                >
                                    <RefreshCw className="size-3.5" /> Ganti
                                </Link>
                            </div>
                            <h2 className="mt-2 text-lg font-extrabold text-navy-900">
                                {fokus.nama_kompetensi_sekolah}
                            </h2>
                            <div className="mt-2 flex flex-wrap gap-1.5">
                                <GapBadge warna={fokus.warna} gap={fokus.gap} />
                                <StatusBadge
                                    status={fokus.status}
                                    label={fokus.status_label}
                                />
                            </div>
                            <div className="mt-4 max-w-sm">
                                <LevelBar
                                    level={fokus.level}
                                    target={fokus.target_level}
                                    warna={fokus.warna}
                                />
                                <p className="mt-1.5 text-xs text-navy-600">
                                    Level {fokus.level} ·{' '}
                                    {namaLevel(fokus.level)} · target{' '}
                                    {fokus.target_level}
                                </p>
                            </div>
                        </section>
                    )}

                    <section className="rounded-3xl border bg-card p-5 shadow-xs">
                        <h2 className="text-base font-bold text-navy-900">
                            Yang perlu dilakukan hari ini
                        </h2>
                        {aktivitasHariIni.length === 0 ? (
                            <p className="mt-3 flex items-center gap-2 text-sm font-medium text-hijau-700">
                                <Sparkles className="size-4" />
                                Semua beres untuk hari ini. Kerja bagus!
                            </p>
                        ) : (
                            <ul className="mt-3 space-y-2">
                                {aktivitasHariIni.map((a, i) => {
                                    const Ikon = ikonAktivitas[a.jenis];

                                    return (
                                        <li key={i}>
                                            <Link
                                                href={hrefAktivitas(a)}
                                                className="group flex items-center gap-3 rounded-2xl border bg-navy-50/40 p-3 transition-colors hover:bg-navy-50"
                                            >
                                                <span
                                                    className={cn(
                                                        'grid size-10 shrink-0 place-items-center rounded-xl',
                                                        a.jenis === 'kuis'
                                                            ? 'bg-gap-sedang-soft text-gap-sedang'
                                                            : 'bg-navy-900 text-white',
                                                    )}
                                                >
                                                    <Ikon className="size-5" />
                                                </span>
                                                <span className="min-w-0 flex-1">
                                                    <span className="block text-sm font-bold text-navy-900">
                                                        {a.judul}
                                                    </span>
                                                    <span className="block truncate text-xs text-muted-foreground">
                                                        {a.keterangan}
                                                    </span>
                                                </span>
                                                <ArrowRight className="size-4 shrink-0 text-navy-300 transition-transform group-hover:translate-x-0.5" />
                                            </Link>
                                        </li>
                                    );
                                })}
                            </ul>
                        )}
                    </section>
                </div>

                <div className="min-w-0 space-y-4">
                    <section className="rounded-3xl border bg-card p-5 shadow-xs">
                        <div className="flex items-center justify-between gap-3">
                            <h2 className="text-base font-bold text-navy-900">
                                Kompetensi yang masih gap
                            </h2>
                            <Link
                                href={siswa.gap().url}
                                className="text-xs font-semibold text-navy-600 hover:text-navy-900"
                            >
                                Lihat semua
                            </Link>
                        </div>
                        {kompetensiGap.length === 0 ? (
                            <p className="mt-3 text-sm text-hijau-700">
                                Semua kompetensi sudah sesuai target.
                            </p>
                        ) : (
                            <ul className="mt-3 divide-y">
                                {kompetensiGap.map((k) => (
                                    <li
                                        key={k.kompetensi_id}
                                        className="flex items-center justify-between gap-3 py-2.5"
                                    >
                                        <span className="min-w-0 text-sm font-semibold text-navy-900">
                                            {k.nama_kompetensi_sekolah}
                                        </span>
                                        <GapBadge warna={k.warna} gap={k.gap} />
                                    </li>
                                ))}
                            </ul>
                        )}
                    </section>

                    <section className="rounded-3xl border bg-card p-5 shadow-xs">
                        <h2 className="text-base font-bold text-navy-900">
                            Rekomendasi materi
                        </h2>
                        {rekomendasi.length === 0 ? (
                            <p className="mt-3 text-sm text-muted-foreground">
                                Belum ada materi yang perlu direkomendasikan.
                            </p>
                        ) : (
                            <ul className="mt-3 space-y-2">
                                {rekomendasi.map((r) => (
                                    <li key={r.materi_id}>
                                        <Link
                                            href={
                                                siswa.belajar.show(r.materi_id)
                                                    .url
                                            }
                                            className="block rounded-2xl border p-3 transition-colors hover:bg-navy-50/60"
                                        >
                                            <span className="text-xs text-muted-foreground">
                                                {r.kompetensi} · Level {r.level}
                                            </span>
                                            <span className="mt-0.5 block text-sm font-bold text-navy-900">
                                                {r.judul}
                                            </span>
                                            <BadgeVerifikasi
                                                terverifikasi={
                                                    r.terverifikasi_industri
                                                }
                                                className="mt-1.5"
                                            />
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </section>

                    <section className="rounded-3xl border bg-card p-5 shadow-xs">
                        <h2 className="text-base font-bold text-navy-900">
                            Logbook terakhir
                        </h2>
                        {logbookTerakhir ? (
                            <div className="mt-2">
                                <p className="text-xs font-semibold text-hijau-600">
                                    {formatTanggalPanjang(
                                        logbookTerakhir.tanggal,
                                    )}
                                </p>
                                <p className="mt-1 line-clamp-3 text-sm text-navy-900">
                                    {logbookTerakhir.aktivitas}
                                </p>
                            </div>
                        ) : (
                            <p className="mt-2 text-sm text-muted-foreground">
                                Anda belum mengisi logbook.
                            </p>
                        )}
                        <Button
                            asChild
                            variant="outline"
                            size="sm"
                            className="mt-3"
                        >
                            <Link href={siswa.logbook.index().url}>
                                <NotebookPen />
                                Buka logbook
                            </Link>
                        </Button>
                    </section>
                </div>
            </div>
        </>
    );
}
