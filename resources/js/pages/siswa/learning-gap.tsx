import { Head, Link } from '@inertiajs/react';
import { BookOpen, MessagesSquare, PartyPopper } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import {
    GapBadge,
    LevelBar,
    namaLevel,
} from '@/components/kompetensi-indikator';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { cn } from '@/lib/utils';
import siswa from '@/routes/siswa';
import type { BarisKompetensi } from '@/types/kompetensi';
import type { Rekomendasi } from '@/types/siswa';

type BarisGap = BarisKompetensi & {
    materi_disarankan: Rekomendasi | null;
    jumlah_materi: number;
    materi_pertama_id: number | null;
};

/**
 * Learning Gap (bagian 6.3): kompetensi dengan gap > 0, urut gap terbesar.
 */
export default function LearningGap({
    kompetensi,
}: {
    kompetensi: BarisGap[];
}) {
    return (
        <>
            <Head title="Learning Gap" />
            <PageHeader
                eyebrow="Learning gap"
                title="Kompetensi yang perlu Anda tingkatkan"
                description="Diurutkan dari gap terbesar. Lulus kuis materi berlevel lebih tinggi untuk menutup gap."
            />

            {kompetensi.length === 0 ? (
                <EmptyState
                    icon={PartyPopper}
                    title="Tidak ada gap"
                    description="Semua kompetensi Anda sudah sesuai target industri."
                />
            ) : (
                <ul className="grid gap-4 md:grid-cols-2">
                    {kompetensi.map((k, i) => {
                        const materiId =
                            k.materi_disarankan?.materi_id ??
                            k.materi_pertama_id;

                        return (
                            <li
                                key={k.kompetensi_id}
                                className={cn(
                                    'flex flex-col rounded-3xl border bg-card p-5 shadow-xs',
                                    i === 0 && 'md:col-span-2',
                                )}
                            >
                                <GapBadge warna={k.warna} gap={k.gap} />
                                <h2 className="mt-3 text-lg font-extrabold text-navy-900">
                                    {k.nama_kompetensi_sekolah}
                                </h2>
                                <p className="mt-0.5 text-sm text-muted-foreground">
                                    {k.aktivitas_kompetensi_industri}
                                </p>

                                <dl className="mt-4 grid grid-cols-3 gap-2 text-center">
                                    <div className="rounded-2xl bg-navy-50/70 p-2.5">
                                        <dt className="text-[11px] font-semibold text-navy-500">
                                            Level Anda
                                        </dt>
                                        <dd className="text-2xl font-extrabold text-navy-900 tabular-nums">
                                            {k.level}
                                        </dd>
                                    </div>
                                    <div className="rounded-2xl bg-navy-50/70 p-2.5">
                                        <dt className="text-[11px] font-semibold text-navy-500">
                                            Target industri
                                        </dt>
                                        <dd className="text-2xl font-extrabold text-navy-900 tabular-nums">
                                            {k.target_level}
                                        </dd>
                                    </div>
                                    <div
                                        className={cn(
                                            'rounded-2xl p-2.5',
                                            k.warna === 'tinggi'
                                                ? 'bg-gap-tinggi-soft'
                                                : 'bg-gap-sedang-soft',
                                        )}
                                    >
                                        <dt className="text-[11px] font-semibold text-navy-700">
                                            Gap
                                        </dt>
                                        <dd className="text-2xl font-extrabold text-navy-900 tabular-nums">
                                            {k.gap}
                                        </dd>
                                    </div>
                                </dl>
                                <div className="mt-3">
                                    <LevelBar
                                        level={k.level}
                                        target={k.target_level}
                                        warna={k.warna}
                                    />
                                    <p className="mt-1.5 text-xs text-navy-600">
                                        Sekarang: {namaLevel(k.level)}
                                    </p>
                                </div>

                                {k.materi_disarankan && (
                                    <p className="mt-4 rounded-2xl border border-dashed px-3 py-2 text-sm text-navy-800">
                                        Disarankan:{' '}
                                        <strong>
                                            {k.materi_disarankan.judul}
                                        </strong>{' '}
                                        (level {k.materi_disarankan.level})
                                    </p>
                                )}

                                <div className="mt-auto flex flex-wrap gap-2 pt-4">
                                    {materiId ? (
                                        <Button asChild>
                                            <Link
                                                href={
                                                    siswa.belajar.show(materiId)
                                                        .url
                                                }
                                            >
                                                <BookOpen />
                                                Pelajari
                                            </Link>
                                        </Button>
                                    ) : (
                                        <Button disabled>
                                            <BookOpen />
                                            Materi belum tersedia
                                        </Button>
                                    )}
                                    <Tooltip>
                                        <TooltipTrigger asChild>
                                            <span tabIndex={0}>
                                                <Button
                                                    variant="outline"
                                                    disabled
                                                >
                                                    <MessagesSquare />
                                                    Tanya AI Mentor
                                                </Button>
                                            </span>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            AI Mentor segera tersedia
                                        </TooltipContent>
                                    </Tooltip>
                                </div>
                            </li>
                        );
                    })}
                </ul>
            )}
        </>
    );
}
