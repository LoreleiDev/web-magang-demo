import { BadgeCheck } from 'lucide-react';
import { cn } from '@/lib/utils';

/**
 * Badge "Diverifikasi oleh industri" (CLAUDE.md bagian 6.4 & 11.2).
 * Tidak dirender sama sekali jika materi belum/tidak diverifikasi.
 */
export function BadgeVerifikasi({
    terverifikasi,
    className,
}: {
    terverifikasi: boolean;
    className?: string;
}) {
    if (!terverifikasi) {
        return null;
    }

    return (
        <span
            className={cn(
                'inline-flex items-center gap-1 rounded-full bg-hijau-50 px-2.5 py-1 text-xs font-bold text-hijau-700 ring-1 ring-hijau-100',
                className,
            )}
        >
            <BadgeCheck className="size-3.5" />
            Diverifikasi oleh industri
        </span>
    );
}
