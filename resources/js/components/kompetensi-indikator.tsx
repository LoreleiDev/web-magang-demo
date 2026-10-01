import { cn } from '@/lib/utils';
import type { StatusKompetensi, WarnaGap } from '@/types/kompetensi';

const labelLevel: Record<number, string> = {
    1: 'Belum mampu',
    2: 'Mampu dengan bimbingan',
    3: 'Mampu mandiri',
    4: 'Mampu mandiri sesuai standar industri',
};

export function namaLevel(level: number): string {
    return labelLevel[level] ?? '-';
}

/**
 * Status kompetensi (CLAUDE.md bagian 10). Warna mengikuti urutan:
 * abu-biru -> biru -> biru tua -> garis biru tua -> hijau (terverifikasi).
 */
const gayaStatus: Record<StatusKompetensi, string> = {
    belum_dipelajari: 'bg-navy-100/70 text-navy-600',
    sedang_dipelajari: 'bg-navy-100 text-navy-800',
    sedang_dipraktikkan: 'bg-navy-700 text-white',
    menunggu_verifikasi:
        'bg-card text-navy-900 ring-1 ring-navy-900 ring-inset',
    terverifikasi: 'bg-hijau-500 text-white',
};

export function StatusBadge({
    status,
    label,
    className,
}: {
    status: StatusKompetensi;
    label: string;
    className?: string;
}) {
    return (
        <span
            className={cn(
                'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-bold whitespace-nowrap',
                gayaStatus[status],
                className,
            )}
        >
            {status === 'menunggu_verifikasi' && (
                <span className="size-1.5 animate-pulse rounded-full bg-navy-900" />
            )}
            {label}
        </span>
    );
}

/**
 * Gap = target − level. Merah ≥ 2, kuning = 1, hijau ≤ 0 (bagian 6.2).
 */
const gayaGap: Record<WarnaGap, { kelas: string; label: string }> = {
    tinggi: {
        kelas: 'bg-gap-tinggi-soft text-gap-tinggi',
        label: 'Gap tinggi',
    },
    sedang: {
        kelas: 'bg-gap-sedang-soft text-navy-900',
        label: 'Perlu penguatan',
    },
    aman: { kelas: 'bg-gap-aman-soft text-hijau-700', label: 'Sesuai target' },
};

export function GapBadge({
    warna,
    gap,
    className,
}: {
    warna: WarnaGap;
    gap: number;
    className?: string;
}) {
    return (
        <span
            className={cn(
                'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-bold whitespace-nowrap',
                gayaGap[warna].kelas,
                className,
            )}
        >
            <span
                className={cn(
                    'size-2 rounded-full',
                    warna === 'tinggi' && 'bg-gap-tinggi',
                    warna === 'sedang' && 'bg-gap-sedang',
                    warna === 'aman' && 'bg-gap-aman',
                )}
            />
            {gayaGap[warna].label}
            {gap > 0 && <span className="tabular-nums">· gap {gap}</span>}
        </span>
    );
}

/**
 * Empat segmen level; segmen sampai level siswa terisi, garis target ditandai.
 */
export function LevelBar({
    level,
    target,
    warna,
}: {
    level: number;
    target: number;
    warna: WarnaGap;
}) {
    return (
        <div
            className="flex gap-1"
            role="img"
            aria-label={`Level ${level} dari target ${target}`}
        >
            {[1, 2, 3, 4].map((n) => (
                <span
                    key={n}
                    className={cn(
                        'relative h-2 flex-1 rounded-full',
                        n <= level
                            ? warna === 'aman'
                                ? 'bg-hijau-500'
                                : warna === 'sedang'
                                  ? 'bg-gap-sedang'
                                  : 'bg-gap-tinggi'
                            : 'bg-navy-100',
                        n === target &&
                            'outline-2 outline-offset-1 outline-navy-900',
                    )}
                />
            ))}
        </div>
    );
}
