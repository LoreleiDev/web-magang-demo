import { Head, Link } from '@inertiajs/react';
import { BookOpen, Factory, Pencil, Plus, Target } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import { namaLevel } from '@/components/kompetensi-indikator';
import { PageHeader } from '@/components/page-header';
import { TanpaProgram } from '@/components/tanpa-program';
import { Button } from '@/components/ui/button';
import guru from '@/routes/guru';

type Kompetensi = {
    id: number;
    nama_kompetensi_sekolah: string;
    aktivitas_kompetensi_industri: string;
    target_level: number;
    pembuat: string;
    jumlah_materi: number;
};

export default function KompetensiIndex({
    program,
    kompetensi,
}: {
    program: { kode: string; nama: string } | null;
    kompetensi: Kompetensi[];
}) {
    return (
        <>
            <Head title="Kompetensi" />
            <PageHeader
                eyebrow={program ? program.nama : 'Kompetensi'}
                title="Kompetensi"
                description="Pasangan kompetensi sekolah dan aktivitas di industri, beserta target level yang harus dicapai siswa."
                actions={
                    program && (
                        <Button asChild>
                            <Link href={guru.kompetensi.create().url}>
                                <Plus />
                                Tambah kompetensi
                            </Link>
                        </Button>
                    )
                }
            />

            {program === null ? (
                <TanpaProgram />
            ) : kompetensi.length === 0 ? (
                <EmptyState
                    icon={Target}
                    title="Belum ada kompetensi"
                    description="Tambahkan kompetensi agar peta kompetensi dan learning gap siswa bisa dihitung."
                />
            ) : (
                <ol className="space-y-3">
                    {kompetensi.map((k, i) => (
                        <li
                            key={k.id}
                            className="grid gap-4 rounded-3xl border bg-card p-5 shadow-xs md:grid-cols-[2.5rem_1fr_1fr_auto] md:items-center"
                        >
                            <span className="hidden size-10 place-items-center rounded-xl bg-navy-50 text-sm font-extrabold text-navy-700 md:grid">
                                {i + 1}
                            </span>
                            <div>
                                <div className="text-xs font-semibold text-navy-500">
                                    Kompetensi sekolah
                                </div>
                                <div className="font-bold text-navy-900">
                                    {k.nama_kompetensi_sekolah}
                                </div>
                            </div>
                            <div>
                                <div className="flex items-center gap-1 text-xs font-semibold text-navy-500">
                                    <Factory className="size-3.5" />
                                    Aktivitas industri
                                </div>
                                <div className="text-sm text-navy-800">
                                    {k.aktivitas_kompetensi_industri}
                                </div>
                            </div>
                            <div className="flex items-center justify-between gap-3 border-t pt-3 md:flex-col md:items-end md:border-none md:pt-0">
                                <div className="text-right">
                                    <span className="rounded-full bg-navy-900 px-2.5 py-1 text-xs font-bold text-white">
                                        Target {k.target_level}
                                    </span>
                                    <div className="mt-1 text-[11px] text-muted-foreground">
                                        {namaLevel(k.target_level)}
                                    </div>
                                </div>
                                <div className="flex items-center gap-2">
                                    <span className="flex items-center gap-1 text-xs text-navy-600">
                                        <BookOpen className="size-3.5" />
                                        {k.jumlah_materi} materi
                                    </span>
                                    <Button asChild variant="outline" size="sm">
                                        <Link
                                            href={
                                                guru.kompetensi.edit(k.id).url
                                            }
                                        >
                                            <Pencil />
                                            Edit
                                        </Link>
                                    </Button>
                                </div>
                            </div>
                        </li>
                    ))}
                </ol>
            )}
        </>
    );
}
