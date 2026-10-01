import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Factory, MessageSquareText } from 'lucide-react';
import { BadgeVerifikasi } from '@/components/badge-verifikasi';
import { MateriBaca } from '@/components/materi-baca';
import { formatTanggal, formatTanggalWaktu } from '@/lib/format';
import { cn } from '@/lib/utils';
import superadmin from '@/routes/superadmin';
import type { MateriDetail, RiwayatVerifikasi } from '@/types/materi';

export default function PemantauanMateriDetail({
    materi,
    riwayatVerifikasi,
}: {
    materi: MateriDetail;
    riwayatVerifikasi: RiwayatVerifikasi[];
}) {
    return (
        <>
            <Head title={materi.judul} />

            <Link
                href={superadmin.materi.index().url}
                className="mb-4 inline-flex items-center gap-1.5 text-sm font-semibold text-navy-600 hover:text-navy-900"
            >
                <ArrowLeft className="size-4" /> Daftar materi
            </Link>

            <header className="mb-8">
                <p className="text-xs font-bold tracking-[0.16em] text-hijau-600 uppercase">
                    {materi.program_keahlian_nama}
                </p>
                <h1 className="mt-1.5 text-2xl font-extrabold tracking-tight text-balance text-navy-900 sm:text-3xl">
                    {materi.judul}
                </h1>
                <BadgeVerifikasi
                    terverifikasi={materi.terverifikasi_industri}
                    className="mt-3"
                />
                <p className="mt-3 text-sm text-muted-foreground">
                    Dibuat oleh {materi.pembuat} · diubah{' '}
                    {formatTanggal(materi.diubah_terakhir)}
                </p>
            </header>

            <div className="grid gap-6 lg:grid-cols-[1fr_20rem] lg:items-start">
                <MateriBaca materi={materi} />

                <aside className="space-y-4 lg:sticky lg:top-6">
                    <section className="rounded-3xl border bg-card p-5 shadow-xs">
                        <h2 className="text-sm font-bold text-navy-900">
                            Kompetensi
                        </h2>
                        <p className="mt-2 text-sm font-semibold text-navy-800">
                            {materi.kompetensi}
                        </p>
                        <p className="mt-2 flex gap-2 text-sm text-muted-foreground">
                            <Factory className="mt-0.5 size-4 shrink-0 text-navy-400" />
                            {materi.aktivitas_industri}
                        </p>
                    </section>

                    <section className="rounded-3xl border bg-card p-5 shadow-xs">
                        <h2 className="text-sm font-bold text-navy-900">
                            Riwayat pemeriksaan industri
                        </h2>
                        {riwayatVerifikasi.length === 0 ? (
                            <p className="mt-2 text-sm text-muted-foreground">
                                Belum pernah diperiksa industri.
                            </p>
                        ) : (
                            <ol className="mt-3 space-y-3">
                                {riwayatVerifikasi.map((v) => (
                                    <li
                                        key={v.id}
                                        className="rounded-2xl bg-navy-50/70 p-3"
                                    >
                                        <span
                                            className={cn(
                                                'rounded-full px-2 py-0.5 text-[11px] font-bold',
                                                v.hasil === 'diverifikasi'
                                                    ? 'bg-hijau-50 text-hijau-700'
                                                    : 'bg-gap-sedang-soft text-navy-900',
                                            )}
                                        >
                                            {v.hasil_label}
                                        </span>
                                        <p className="mt-2 text-xs text-navy-600">
                                            {v.pemeriksa} · {v.perusahaan}
                                            <br />
                                            {formatTanggalWaktu(v.tanggal)}
                                        </p>
                                        {v.masukan && (
                                            <p className="mt-2 flex gap-2 text-sm text-navy-900">
                                                <MessageSquareText className="mt-0.5 size-4 shrink-0 text-navy-400" />
                                                {v.masukan}
                                            </p>
                                        )}
                                    </li>
                                ))}
                            </ol>
                        )}
                    </section>
                </aside>
            </div>
        </>
    );
}
