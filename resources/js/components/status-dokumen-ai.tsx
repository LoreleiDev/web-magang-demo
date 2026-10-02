import { CircleCheck, CircleX, LoaderCircle } from 'lucide-react';
import { cn } from '@/lib/utils';

export type StatusAi = 'menunggu' | 'siap' | 'gagal';

/**
 * Status dokumen di knowledge base AI Mentor (Gemini File Search).
 */
export function StatusDokumenAi({
    status,
    pesanError,
}: {
    status: StatusAi;
    pesanError?: string | null;
}) {
    const gaya = {
        menunggu: {
            label: 'Sedang diproses AI',
            kelas: 'bg-navy-50 text-navy-700',
            Icon: LoaderCircle,
        },
        siap: {
            label: 'Siap dipakai AI Mentor',
            kelas: 'bg-hijau-50 text-hijau-700',
            Icon: CircleCheck,
        },
        gagal: {
            label: 'Gagal diproses AI',
            kelas: 'bg-gap-tinggi-soft text-gap-tinggi',
            Icon: CircleX,
        },
    }[status];

    return (
        <span
            className={cn(
                'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-bold',
                gaya.kelas,
            )}
            title={status === 'gagal' ? (pesanError ?? undefined) : undefined}
        >
            <gaya.Icon
                className={cn(
                    'size-3',
                    status === 'menunggu' && 'animate-spin',
                )}
            />
            {gaya.label}
        </span>
    );
}
