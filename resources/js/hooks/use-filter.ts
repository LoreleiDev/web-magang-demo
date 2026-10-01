import { router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

type NilaiFilter = Record<string, string | number | null | undefined>;

/**
 * Filter daftar lewat query string. Perubahan teks (pencarian) ditunda 350 ms
 * agar tidak mengirim permintaan setiap ketikan.
 */
export function useFilter<T extends NilaiFilter>(url: string, awal: T) {
    const [nilai, setNilai] = useState<T>(awal);
    const pertama = useRef(true);

    useEffect(() => {
        if (pertama.current) {
            pertama.current = false;

            return;
        }

        const timer = setTimeout(() => {
            const query = Object.fromEntries(
                Object.entries(nilai).filter(
                    ([, v]) => v !== null && v !== undefined && v !== '',
                ),
            );

            router.get(url, query, {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            });
        }, 350);

        return () => clearTimeout(timer);
    }, [nilai, url]);

    const ubah = <K extends keyof T>(kunci: K, v: T[K]) =>
        setNilai((lama) => ({ ...lama, [kunci]: v }));

    return [nilai, ubah] as const;
}
