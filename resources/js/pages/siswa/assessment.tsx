import { Head, Link } from '@inertiajs/react';
import {
    CheckCircle2,
    CircleDashed,
    CircleX,
    ClipboardCheck,
    RotateCcw,
} from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { formatTanggalWaktu } from '@/lib/format';
import { cn } from '@/lib/utils';
import siswa from '@/routes/siswa';
import type { StatusKuis } from '@/types/siswa';

type Kuis = StatusKuis & {
    materi_id: number;
    judul: string;
    kompetensi: string;
    level: number;
    nilai_minimal: number;
    penguatan: { id: number; judul: string }[];
};

type Riwayat = {
    id: number;
    materi_id: number;
    materi: string;
    skor: number;
    lulus: boolean;
    tanggal: string;
};

/**
 * Assessment (bagian 6.7): skor tiap kuis dan rekomendasi penguatan bila belum lulus.
 */
export default function Assessment({
    kuis,
    riwayat,
}: {
    kuis: Kuis[];
    riwayat: Riwayat[];
}) {
    return (
        <>
            <Head title="Assessment" />
            <PageHeader
                eyebrow="Assessment"
                title="Hasil kuis"
                description="Kuis boleh diulang. Yang dihitung adalah skor terbaik Anda."
            />

            {kuis.length === 0 ? (
                <EmptyState
                    icon={ClipboardCheck}
                    title="Belum ada kuis"
                    description="Kuis tersedia setelah guru membuat materi."
                />
            ) : (
                <ul className="grid gap-3 md:grid-cols-2">
                    {kuis.map((k) => {
                        const status =
                            k.percobaan === 0
                                ? 'belum'
                                : k.lulus
                                  ? 'lulus'
                                  : 'gagal';

                        return (
                            <li
                                key={k.materi_id}
                                className="flex flex-col rounded-3xl border bg-card p-5 shadow-xs"
                            >
                                <div className="flex items-start justify-between gap-3">
                                    <div className="min-w-0">
                                        <p className="text-xs text-muted-foreground">
                                            {k.kompetensi} · Level {k.level}
                                        </p>
                                        <h2 className="mt-0.5 font-bold text-navy-900">
                                            {k.judul}
                                        </h2>
                                    </div>
                                    <div className="shrink-0 text-right">
                                        <div className="text-3xl font-extrabold text-navy-900 tabular-nums">
                                            {k.terbaik ?? '-'}
                                        </div>
                                        <div className="text-[11px] text-muted-foreground">
                                            minimal {k.nilai_minimal}
                                        </div>
                                    </div>
                                </div>

                                <div className="mt-3 h-2 overflow-hidden rounded-full bg-navy-100">
                                    <div
                                        className={cn(
                                            'h-full rounded-full',
                                            status === 'lulus'
                                                ? 'bg-hijau-500'
                                                : 'bg-gap-tinggi',
                                        )}
                                        style={{ width: `${k.terbaik ?? 0}%` }}
                                    />
                                </div>

                                <p
                                    className={cn(
                                        'mt-3 flex items-center gap-1.5 text-sm font-semibold',
                                        status === 'lulus' && 'text-hijau-700',
                                        status === 'gagal' && 'text-gap-tinggi',
                                        status === 'belum' && 'text-navy-500',
                                    )}
                                >
                                    {status === 'lulus' && (
                                        <CheckCircle2 className="size-4" />
                                    )}
                                    {status === 'gagal' && (
                                        <CircleX className="size-4" />
                                    )}
                                    {status === 'belum' && (
                                        <CircleDashed className="size-4" />
                                    )}
                                    {status === 'lulus'
                                        ? 'Lulus'
                                        : status === 'gagal'
                                          ? 'Belum lulus'
                                          : 'Belum dikerjakan'}
                                    {k.percobaan > 0 && (
                                        <span className="font-normal text-muted-foreground">
                                            · {k.percobaan} percobaan
                                        </span>
                                    )}
                                </p>

                                {status === 'gagal' && (
                                    <div className="mt-3 rounded-2xl bg-gap-sedang-soft p-3 text-sm text-navy-900">
                                        <p className="font-bold">
                                            Rekomendasi penguatan
                                        </p>
                                        <p className="mt-0.5">
                                            Ulangi langkah Konsep Dasar dan
                                            Latihan
                                            {k.penguatan.length > 0 &&
                                                ', lalu pelajari juga:'}
                                        </p>
                                        {k.penguatan.length > 0 && (
                                            <ul className="mt-1.5 list-inside list-disc">
                                                {k.penguatan.map((p) => (
                                                    <li key={p.id}>
                                                        <Link
                                                            href={
                                                                siswa.belajar.show(
                                                                    p.id,
                                                                ).url
                                                            }
                                                            className="font-semibold underline-offset-2 hover:underline"
                                                        >
                                                            {p.judul}
                                                        </Link>
                                                    </li>
                                                ))}
                                            </ul>
                                        )}
                                    </div>
                                )}

                                <div className="mt-auto pt-4">
                                    <Button
                                        asChild
                                        size="sm"
                                        variant={
                                            status === 'lulus'
                                                ? 'outline'
                                                : 'default'
                                        }
                                    >
                                        <Link
                                            href={
                                                siswa.belajar.show(k.materi_id)
                                                    .url
                                            }
                                        >
                                            {status === 'belum' ? (
                                                <ClipboardCheck />
                                            ) : (
                                                <RotateCcw />
                                            )}
                                            {status === 'belum'
                                                ? 'Kerjakan kuis'
                                                : 'Buka materi & ulangi'}
                                        </Link>
                                    </Button>
                                </div>
                            </li>
                        );
                    })}
                </ul>
            )}

            {riwayat.length > 0 && (
                <section className="mt-10">
                    <h2 className="mb-3 text-base font-bold text-navy-900">
                        Riwayat percobaan
                    </h2>
                    <ul className="overflow-hidden rounded-3xl border bg-card shadow-xs">
                        {riwayat.map((r) => (
                            <li
                                key={r.id}
                                className="flex items-center gap-3 border-b px-5 py-3 last:border-none"
                            >
                                <div className="min-w-0 flex-1">
                                    <div className="truncate text-sm font-semibold text-navy-900">
                                        {r.materi}
                                    </div>
                                    <div className="text-xs text-muted-foreground">
                                        {formatTanggalWaktu(r.tanggal)}
                                    </div>
                                </div>
                                <span className="font-extrabold text-navy-900 tabular-nums">
                                    {r.skor}
                                </span>
                                <span
                                    className={cn(
                                        'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-bold',
                                        r.lulus
                                            ? 'bg-hijau-50 text-hijau-700'
                                            : 'bg-gap-tinggi-soft text-gap-tinggi',
                                    )}
                                >
                                    {r.lulus ? (
                                        <CheckCircle2 className="size-3" />
                                    ) : (
                                        <CircleX className="size-3" />
                                    )}
                                    {r.lulus ? 'Lulus' : 'Belum lulus'}
                                </span>
                            </li>
                        ))}
                    </ul>
                </section>
            )}
        </>
    );
}
