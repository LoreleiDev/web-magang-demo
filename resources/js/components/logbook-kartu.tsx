import { ChevronDown, Paperclip, Sparkles } from 'lucide-react';
import { formatTanggalPanjang } from '@/lib/format';
import type { Logbook } from '@/types/kompetensi';

const isian: { kunci: keyof Logbook; label: string }[] = [
    { kunci: 'peralatan_software', label: 'Peralatan/software' },
    { kunci: 'sudah_dipahami', label: 'Sudah dipahami' },
    { kunci: 'baru_ditemui', label: 'Baru ditemui' },
    { kunci: 'kesulitan', label: 'Kesulitan' },
    {
        kunci: 'pengetahuan_sekolah_digunakan',
        label: 'Pengetahuan sekolah yang digunakan',
    },
    { kunci: 'ingin_dipelajari', label: 'Ingin dipelajari' },
];

/**
 * Kartu logbook baca-saja yang bisa dibuka untuk melihat semua isian.
 */
export function LogbookKartu({
    logbook,
    tampilkanSiswa = true,
}: {
    logbook: Logbook;
    tampilkanSiswa?: boolean;
}) {
    return (
        <details className="group rounded-3xl border bg-card shadow-xs open:shadow-md">
            <summary className="flex cursor-pointer list-none items-start gap-4 p-5 [&::-webkit-details-marker]:hidden">
                <div className="min-w-0 flex-1">
                    {tampilkanSiswa && (
                        <div className="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs">
                            <span className="font-bold text-navy-900">
                                {logbook.siswa}
                            </span>
                            {logbook.kelompok && (
                                <span className="text-muted-foreground">
                                    · {logbook.kelompok}
                                </span>
                            )}
                        </div>
                    )}
                    <p className="mt-0.5 text-xs font-semibold text-hijau-600">
                        {formatTanggalPanjang(logbook.tanggal)}
                    </p>
                    <p className="mt-2 line-clamp-2 text-sm text-navy-900 group-open:line-clamp-none">
                        {logbook.aktivitas}
                    </p>
                    <div className="mt-2 flex gap-3 text-xs text-navy-500">
                        {logbook.url_bukti && (
                            <a
                                href={logbook.url_bukti}
                                target="_blank"
                                rel="noopener"
                                onClick={(e) => e.stopPropagation()}
                                className="flex items-center gap-1 font-semibold underline-offset-2 hover:underline"
                            >
                                <Paperclip className="size-3.5" /> Lihat bukti
                            </a>
                        )}
                        {logbook.sudah_dianalisis && (
                            <span className="flex items-center gap-1">
                                <Sparkles className="size-3.5" /> Sudah
                                dianalisis AI
                            </span>
                        )}
                    </div>
                </div>
                <ChevronDown className="mt-1 size-5 shrink-0 text-navy-300 transition-transform group-open:rotate-180" />
            </summary>
            <dl className="grid gap-3 border-t px-5 py-4 sm:grid-cols-2">
                {isian.map(({ kunci, label }) => (
                    <div key={kunci} className="rounded-2xl bg-navy-50/60 p-3">
                        <dt className="text-xs font-semibold text-navy-500">
                            {label}
                        </dt>
                        <dd className="mt-1 text-sm whitespace-pre-line text-navy-900">
                            {(logbook[kunci] as string | null) ?? '-'}
                        </dd>
                    </div>
                ))}
            </dl>
        </details>
    );
}
