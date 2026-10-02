import { Head, Link } from '@inertiajs/react';
import { BookOpen, ChevronRight, CircleDashed, RefreshCw } from 'lucide-react';
import { BadgeVerifikasi } from '@/components/badge-verifikasi';
import { EmptyState } from '@/components/empty-state';
import { PageHeader } from '@/components/page-header';
import { formatTanggal } from '@/lib/format';
import { cn } from '@/lib/utils';
import industri from '@/routes/industri';
import type { MateriRingkas } from '@/types/materi';

type Materi = MateriRingkas & {
    pemeriksaan_saya: {
        hasil: 'diverifikasi' | 'belum_sesuai';
        hasil_label: string;
        tanggal: string | null;
        perlu_ulang: boolean;
    } | null;
};

/**
 * Verifikasi Materi (bagian 8 & 11.2): daftar materi untuk program keahlian
 * siswa yang magang di perusahaan ini.
 */
export default function IndustriMateriIndex({ materi }: { materi: Materi[] }) {
    const belum = materi.filter(
        (m) => m.pemeriksaan_saya === null || m.pemeriksaan_saya.perlu_ulang,
    ).length;

    return (
        <>
            <Head title="Verifikasi Materi" />
            <PageHeader
                eyebrow="Verifikasi materi"
                title="Materi dari sekolah"
                description="Baca materi yang dipelajari siswa, lalu verifikasi atau beri masukan kepada guru pembuatnya."
            />

            {belum > 0 && (
                <p className="mb-5 text-sm text-navy-700">
                    <strong>{belum} materi</strong> belum Anda periksa atau
                    perlu diperiksa ulang karena sudah diedit guru.
                </p>
            )}

            {materi.length === 0 ? (
                <EmptyState
                    icon={BookOpen}
                    title="Belum ada materi"
                    description="Materi muncul setelah guru membuatnya untuk program keahlian siswa magang Anda."
                />
            ) : (
                <ul className="grid gap-3 md:grid-cols-2">
                    {materi.map((m) => {
                        const p = m.pemeriksaan_saya;

                        return (
                            <li key={m.id}>
                                <Link
                                    href={industri.materi.show(m.id).url}
                                    className="group flex h-full items-start gap-4 rounded-3xl border bg-card p-5 shadow-xs transition-shadow hover:shadow-md"
                                >
                                    <div className="min-w-0 flex-1">
                                        <p className="text-xs text-muted-foreground">
                                            {m.program_keahlian} ·{' '}
                                            {m.kompetensi} · Level {m.level}
                                        </p>
                                        <h2 className="mt-1 leading-snug font-bold text-navy-900">
                                            {m.judul}
                                        </h2>
                                        <BadgeVerifikasi
                                            terverifikasi={
                                                m.terverifikasi_industri
                                            }
                                            className="mt-2"
                                        />
                                        <p className="mt-3 flex items-center gap-1.5 text-xs font-semibold">
                                            {p === null ? (
                                                <span className="flex items-center gap-1.5 text-navy-500">
                                                    <CircleDashed className="size-3.5" />
                                                    Belum Anda periksa
                                                </span>
                                            ) : p.perlu_ulang ? (
                                                <span className="flex items-center gap-1.5 text-gap-sedang">
                                                    <RefreshCw className="size-3.5" />
                                                    Diedit guru sejak Anda
                                                    periksa
                                                </span>
                                            ) : (
                                                <span
                                                    className={cn(
                                                        p.hasil ===
                                                            'diverifikasi'
                                                            ? 'text-hijau-700'
                                                            : 'text-navy-700',
                                                    )}
                                                >
                                                    Anda: {p.hasil_label} ·{' '}
                                                    {formatTanggal(p.tanggal)}
                                                </span>
                                            )}
                                        </p>
                                    </div>
                                    <ChevronRight className="mt-1 size-5 shrink-0 text-navy-300 transition-transform group-hover:translate-x-0.5" />
                                </Link>
                            </li>
                        );
                    })}
                </ul>
            )}
        </>
    );
}
