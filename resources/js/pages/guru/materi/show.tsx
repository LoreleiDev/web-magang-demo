import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Factory, Pencil } from 'lucide-react';
import { BadgeVerifikasi } from '@/components/badge-verifikasi';
import { MateriBaca } from '@/components/materi-baca';
import { Button } from '@/components/ui/button';
import { formatTanggal } from '@/lib/format';
import guru from '@/routes/guru';
import type { MateriDetail } from '@/types/materi';

export default function GuruMateriShow({
    materi,
    bisaEdit,
}: {
    materi: MateriDetail;
    bisaEdit: boolean;
}) {
    return (
        <>
            <Head title={materi.judul} />
            <Link
                href={guru.materi.index().url}
                className="mb-4 inline-flex items-center gap-1.5 text-sm font-semibold text-navy-600 hover:text-navy-900"
            >
                <ArrowLeft className="size-4" /> Daftar materi
            </Link>

            <header className="mb-8 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p className="text-xs font-bold tracking-[0.16em] text-hijau-600 uppercase">
                        Pratinjau · {materi.program_keahlian_nama}
                    </p>
                    <h1 className="mt-1.5 text-2xl font-extrabold tracking-tight text-balance text-navy-900 sm:text-3xl">
                        {materi.judul}
                    </h1>
                    <BadgeVerifikasi
                        terverifikasi={materi.terverifikasi_industri}
                        className="mt-3"
                    />
                    <p className="mt-3 flex gap-1.5 text-sm text-muted-foreground">
                        <Factory className="mt-0.5 size-4 shrink-0 text-navy-400" />
                        {materi.kompetensi} · diubah{' '}
                        {formatTanggal(materi.diubah_terakhir)}
                    </p>
                </div>
                {bisaEdit && (
                    <Button asChild className="shrink-0">
                        <Link href={guru.materi.edit(materi.id).url}>
                            <Pencil />
                            Edit materi
                        </Link>
                    </Button>
                )}
            </header>

            <MateriBaca materi={materi} />
        </>
    );
}
