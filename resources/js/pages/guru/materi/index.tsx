import { Head, Link } from '@inertiajs/react';
import { BookOpen, Eye, MessageSquareText, Pencil, Plus } from 'lucide-react';
import { BadgeVerifikasi } from '@/components/badge-verifikasi';
import { EmptyState } from '@/components/empty-state';
import { PageHeader } from '@/components/page-header';
import { TanpaProgram } from '@/components/tanpa-program';
import { Button } from '@/components/ui/button';
import { formatTanggal, formatTanggalWaktu } from '@/lib/format';
import { cn } from '@/lib/utils';
import guru from '@/routes/guru';
import type { MateriRingkas } from '@/types/materi';

type Materi = MateriRingkas & {
    masukan_terakhir: {
        hasil: 'diverifikasi' | 'belum_sesuai';
        hasil_label: string;
        masukan: string | null;
        pemeriksa: string;
        perusahaan: string;
        tanggal: string | null;
    } | null;
};

export default function GuruMateriIndex({
    program,
    materi,
}: {
    program: { kode: string; nama: string } | null;
    materi: Materi[];
}) {
    return (
        <>
            <Head title="Materi" />
            <PageHeader
                eyebrow={program?.nama ?? 'Materi'}
                title="Materi pembelajaran"
                description="Materi 8 langkah untuk semua siswa di program keahlian Anda. Industri dapat memverifikasi dan memberi masukan."
                actions={
                    program && (
                        <Button asChild>
                            <Link href={guru.materi.create().url}>
                                <Plus />
                                Tambah materi
                            </Link>
                        </Button>
                    )
                }
            />

            {program === null ? (
                <TanpaProgram />
            ) : materi.length === 0 ? (
                <EmptyState
                    icon={BookOpen}
                    title="Belum ada materi"
                    description="Buat materi pertama untuk kompetensi yang paling sering menjadi gap siswa."
                />
            ) : (
                <ul className="grid gap-4 lg:grid-cols-2">
                    {materi.map((m) => (
                        <li
                            key={m.id}
                            className="flex flex-col rounded-3xl border bg-card p-2 shadow-xs"
                        >
                            <div className="flex-1 p-4">
                                <p className="text-xs font-semibold text-navy-500">
                                    {m.kompetensi}
                                </p>
                                <h2 className="mt-1 text-base leading-snug font-bold text-navy-900">
                                    {m.judul}
                                </h2>
                                <BadgeVerifikasi
                                    terverifikasi={m.terverifikasi_industri}
                                    className="mt-2"
                                />
                                <p className="mt-2 text-xs text-muted-foreground">
                                    {m.pembuat} · diubah{' '}
                                    {formatTanggal(m.diubah_terakhir)}
                                </p>
                            </div>

                            <div className="rounded-2xl bg-navy-50/70 p-4">
                                <div className="text-xs font-semibold text-navy-500">
                                    Masukan terakhir dari industri
                                </div>
                                {m.masukan_terakhir ? (
                                    <div className="mt-1.5">
                                        <span
                                            className={cn(
                                                'rounded-full px-2 py-0.5 text-[11px] font-bold',
                                                m.masukan_terakhir.hasil ===
                                                    'diverifikasi'
                                                    ? 'bg-hijau-50 text-hijau-700'
                                                    : 'bg-gap-sedang-soft text-navy-900',
                                            )}
                                        >
                                            {m.masukan_terakhir.hasil_label}
                                        </span>
                                        {m.masukan_terakhir.masukan && (
                                            <p className="mt-2 flex gap-2 text-sm text-navy-900">
                                                <MessageSquareText className="mt-0.5 size-4 shrink-0 text-navy-400" />
                                                <span className="line-clamp-3">
                                                    {m.masukan_terakhir.masukan}
                                                </span>
                                            </p>
                                        )}
                                        <p className="mt-1.5 text-xs text-navy-600">
                                            {m.masukan_terakhir.pemeriksa} ·{' '}
                                            {m.masukan_terakhir.perusahaan} ·{' '}
                                            {formatTanggalWaktu(
                                                m.masukan_terakhir.tanggal,
                                            )}
                                        </p>
                                    </div>
                                ) : (
                                    <p className="mt-1 text-sm text-muted-foreground">
                                        Belum diperiksa industri.
                                    </p>
                                )}
                                <div className="mt-4 flex gap-2">
                                    <Button asChild size="sm">
                                        <Link href={guru.materi.edit(m.id).url}>
                                            <Pencil />
                                            Edit
                                        </Link>
                                    </Button>
                                    <Button
                                        asChild
                                        size="sm"
                                        variant="outline"
                                        className="bg-card"
                                    >
                                        <Link href={guru.materi.show(m.id).url}>
                                            <Eye />
                                            Pratinjau
                                        </Link>
                                    </Button>
                                </div>
                            </div>
                        </li>
                    ))}
                </ul>
            )}
        </>
    );
}
