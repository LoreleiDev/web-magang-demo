import type { ClassValue } from 'clsx';
import { clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

/**
 * Dua huruf awal nama untuk avatar, contoh "Budi Santoso, S.Kom." -> "BS".
 */
export function inisial(nama: string): string {
    return nama
        .split(',')[0]
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((kata) => kata[0]?.toUpperCase() ?? '')
        .join('');
}
