import { Link } from '@inertiajs/react';
import {
    BadgeCheck,
    ChevronRight,
    ClipboardCheck,
    NotebookPen,
    Star,
    TriangleAlert,
    Trophy,
} from 'lucide-react';
import { formatTanggal, formatTanggalWaktu } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { RingkasanSiswa } from '@/types/kompetensi';

/**
 * Kartu ringkasan satu siswa di dashboard guru/industri (bagian 7 & 8):
 * progress, learning gap, aktivitas terakhir, logbook, assessment, verifikasi.
 */
export function KartuSiswa({
    siswa,
    href,
}: {
    siswa: RingkasanSiswa;
    href: string;
}) {
    const { progres } = siswa;

    return (
        <Link
            href={href}
            className="group flex h-full min-w-0 flex-col rounded-3xl border bg-card p-5 shadow-xs transition-shadow hover:shadow-md"
        >
            <div className="flex items-start justify-between gap-3">
                <div className="min-w-0">
                    <h3 className="truncate font-bold text-navy-900">
                        {siswa.nama}
                    </h3>
                    <p className="truncate text-xs text-muted-foreground">
                        NIS {siswa.id_siswa} ·{' '}
                        {siswa.unit_kerja ?? (
                            <span className="text-gap-sedang">
                                belum memilih unit kerja
                            </span>
                        )}
                    </p>
                </div>
                <ChevronRight className="mt-1 size-5 shrink-0 text-navy-300 transition-transform group-hover:translate-x-0.5" />
            </div>

            <dl className="mt-4 grid grid-cols-2 gap-2 text-xs">
                <div className="min-w-0 rounded-2xl border border-dashed px-3 py-2">
                    <dt className="flex items-center gap-1 font-semibold text-navy-500">
                        <Star className="size-3.5 fill-hijau-500 text-hijau-500" />
                        Kompetensi utama
                    </dt>
                    <dd className="mt-0.5 line-clamp-2 font-semibold text-navy-900">
                        {siswa.kompetensi_utama ?? 'Belum dipilih'}
                    </dd>
                </div>
                <div className="min-w-0 rounded-2xl bg-navy-50/70 px-3 py-2">
                    <dt className="flex items-center gap-1 font-semibold text-navy-500">
                        <Trophy className="size-3.5 text-navy-500" />
                        Paling dikuasai
                    </dt>
                    <dd className="mt-0.5 font-semibold text-navy-900">
                        {siswa.paling_dikuasai ? (
                            <>
                                <span className="line-clamp-2">
                                    {siswa.paling_dikuasai.nama}
                                </span>
                                <span className="font-normal text-navy-600">
                                    Level {siswa.paling_dikuasai.level}
                                    {siswa.paling_dikuasai.terverifikasi &&
                                        ' · terverifikasi'}
                                </span>
                            </>
                        ) : (
                            <span className="font-normal text-muted-foreground">
                                Belum ada yang naik level
                            </span>
                        )}
                    </dd>
                </div>
            </dl>

            <div className="mt-4">
                <div className="flex items-baseline justify-between text-sm">
                    <span className="font-semibold text-navy-700">Progres</span>
                    <span className="font-extrabold text-navy-900 tabular-nums">
                        {progres.persen}%
                    </span>
                </div>
                <div className="mt-1.5 h-2 overflow-hidden rounded-full bg-navy-100">
                    <div
                        className="h-full rounded-full bg-hijau-500"
                        style={{ width: `${progres.persen}%` }}
                    />
                </div>
                <p className="mt-1.5 text-xs text-muted-foreground">
                    {progres.dikuasai} dari {progres.total} kompetensi sesuai
                    target
                </p>
            </div>

            <div className="mt-4 flex flex-wrap gap-1.5">
                <span
                    className={cn(
                        'inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold',
                        progres.gap > 0
                            ? 'bg-gap-tinggi-soft text-gap-tinggi'
                            : 'bg-gap-aman-soft text-hijau-700',
                    )}
                >
                    <TriangleAlert className="size-3.5" />
                    {progres.gap} gap
                </span>
                <span className="inline-flex items-center gap-1 rounded-full bg-hijau-50 px-2.5 py-1 text-xs font-semibold text-hijau-700">
                    <BadgeCheck className="size-3.5" />
                    {progres.terverifikasi} terverifikasi
                </span>
                <span className="inline-flex items-center gap-1 rounded-full bg-navy-50 px-2.5 py-1 text-xs font-semibold text-navy-700">
                    <NotebookPen className="size-3.5" />
                    {siswa.jumlah_logbook} logbook
                </span>
                <span className="inline-flex items-center gap-1 rounded-full bg-navy-50 px-2.5 py-1 text-xs font-semibold text-navy-700">
                    <ClipboardCheck className="size-3.5" />
                    {siswa.rata_skor === null
                        ? 'belum kuis'
                        : `skor ${siswa.rata_skor}`}
                </span>
            </div>

            <div className="mt-auto pt-4">
                <div className="rounded-2xl bg-navy-50/70 px-3 py-2.5 text-xs">
                    <div className="font-semibold text-navy-500">
                        Aktivitas terakhir
                    </div>
                    {siswa.aktivitas_terakhir ? (
                        <div className="mt-0.5 text-navy-900">
                            <span className="line-clamp-2">
                                {siswa.aktivitas_terakhir.keterangan}
                            </span>
                            <span className="text-muted-foreground">
                                {siswa.aktivitas_terakhir.jenis === 'logbook'
                                    ? formatTanggal(siswa.logbook_terakhir)
                                    : formatTanggalWaktu(
                                          siswa.aktivitas_terakhir.tanggal,
                                      )}
                            </span>
                        </div>
                    ) : (
                        <div className="mt-0.5 text-muted-foreground">
                            Belum ada aktivitas.
                        </div>
                    )}
                </div>
            </div>
        </Link>
    );
}
