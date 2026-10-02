import { Head, Link } from '@inertiajs/react';
import { BookOpen, CheckCircle2, ChevronRight, Target } from 'lucide-react';
import { BadgeVerifikasi } from '@/components/badge-verifikasi';
import { EmptyState } from '@/components/empty-state';
import { LevelBar } from '@/components/kompetensi-indikator';
import { PageHeader } from '@/components/page-header';
import { cn } from '@/lib/utils';
import siswa from '@/routes/siswa';
import type { WarnaGap } from '@/types/kompetensi';
import type { MateriRingkas } from '@/types/materi';
import type { StatusKuis } from '@/types/siswa';

type Kelompok = {
    kompetensi_id: number;
    nama: string;
    level: number;
    target_level: number;
    warna: WarnaGap;
    materi: (MateriRingkas & { status_kuis: StatusKuis })[];
};

/**
 * Daftar materi per kompetensi (bagian 6.4), dengan badge verifikasi industri.
 */
export default function BelajarIndex({
    kompetensi,
    fokusId,
}: {
    kompetensi: Kelompok[];
    fokusId: number | null;
}) {
    // Kompetensi yang sedang dijalani tampil paling atas.
    const urut = [...kompetensi].sort(
        (a, b) =>
            Number(b.kompetensi_id === fokusId) -
            Number(a.kompetensi_id === fokusId),
    );

    return (
        <>
            <Head title="Belajar" />
            <PageHeader
                eyebrow="Belajar"
                title="Materi pembelajaran"
                description="Setiap materi berisi 8 langkah singkat dan diakhiri kuis. Lulus kuis untuk menaikkan level kompetensi Anda."
            />

            {urut.length === 0 ? (
                <EmptyState
                    icon={BookOpen}
                    title="Belum ada materi"
                    description="Guru belum membuat materi untuk program keahlian Anda."
                />
            ) : (
                <div className="space-y-8">
                    {urut.map((k) => (
                        <section key={k.kompetensi_id}>
                            <div className="mb-3 flex flex-wrap items-end justify-between gap-3">
                                <div>
                                    {k.kompetensi_id === fokusId && (
                                        <p className="mb-1 flex items-center gap-1 text-xs font-bold text-hijau-600">
                                            <Target className="size-3.5" />{' '}
                                            Sedang Anda jalani
                                        </p>
                                    )}
                                    <h2 className="text-lg font-extrabold text-navy-900">
                                        {k.nama}
                                    </h2>
                                </div>
                                <div className="w-40">
                                    <LevelBar
                                        level={k.level}
                                        target={k.target_level}
                                        warna={k.warna}
                                    />
                                    <p className="mt-1 text-right text-xs text-navy-600">
                                        Level {k.level} / target{' '}
                                        {k.target_level}
                                    </p>
                                </div>
                            </div>
                            <ul className="grid gap-3 md:grid-cols-2">
                                {k.materi.map((m) => (
                                    <li key={m.id}>
                                        <Link
                                            href={siswa.belajar.show(m.id).url}
                                            className="group flex h-full items-start gap-4 rounded-3xl border bg-card p-4 shadow-xs transition-shadow hover:shadow-md"
                                        >
                                            <span
                                                className={cn(
                                                    'grid size-12 shrink-0 place-items-center rounded-2xl text-center leading-none',
                                                    m.status_kuis.lulus
                                                        ? 'bg-hijau-500 text-white'
                                                        : 'bg-navy-50 text-navy-700',
                                                )}
                                            >
                                                {m.status_kuis.lulus ? (
                                                    <CheckCircle2 className="size-6" />
                                                ) : (
                                                    <span>
                                                        <span className="block text-[10px] font-semibold">
                                                            Level
                                                        </span>
                                                        <span className="text-lg font-extrabold">
                                                            {m.level}
                                                        </span>
                                                    </span>
                                                )}
                                            </span>
                                            <div className="min-w-0 flex-1">
                                                <h3 className="leading-snug font-bold text-navy-900">
                                                    {m.judul}
                                                </h3>
                                                <BadgeVerifikasi
                                                    terverifikasi={
                                                        m.terverifikasi_industri
                                                    }
                                                    className="mt-1.5"
                                                />
                                                <p className="mt-1.5 text-xs text-muted-foreground">
                                                    {m.status_kuis.lulus
                                                        ? `Lulus · skor terbaik ${m.status_kuis.terbaik}`
                                                        : m.status_kuis
                                                                .percobaan > 0
                                                          ? `Skor terbaik ${m.status_kuis.terbaik} · perlu ${m.nilai_minimal}`
                                                          : `Kuis belum dikerjakan · lulus ≥ ${m.nilai_minimal}`}
                                                </p>
                                            </div>
                                            <ChevronRight className="mt-1 size-5 shrink-0 text-navy-300 transition-transform group-hover:translate-x-0.5" />
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        </section>
                    ))}
                </div>
            )}
        </>
    );
}
