import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { useState } from 'react';
import type { DataDetailSiswa } from '@/components/detail-siswa';
import { DetailSiswa } from '@/components/detail-siswa';
import { namaLevel } from '@/components/kompetensi-indikator';
import { cn } from '@/lib/utils';
import guru from '@/routes/guru';
import type { BarisKompetensi } from '@/types/kompetensi';

export default function GuruSiswa(props: DataDetailSiswa) {
    return (
        <>
            <Head title={props.siswa.nama} />
            <Link
                href={guru.dashboard().url}
                className="mb-4 inline-flex items-center gap-1.5 text-sm font-semibold text-navy-600 hover:text-navy-900"
            >
                <ArrowLeft className="size-4" /> Daftar siswa
            </Link>

            <DetailSiswa
                data={props}
                aksiKompetensi={(baris) => (
                    <PilihLevel siswaId={props.siswa.id} baris={baris} />
                )}
            />
        </>
    );
}

/**
 * Guru mengisi level 1–4. Disimpan langsung saat tombol level ditekan.
 */
function PilihLevel({
    siswaId,
    baris,
}: {
    siswaId: number;
    baris: BarisKompetensi;
}) {
    const [menyimpan, setMenyimpan] = useState<number | null>(null);
    const levelAktif = baris.level_diisi ? baris.level : null;

    const simpan = (level: number) => {
        if (level === levelAktif) {
            return;
        }

        router.put(
            guru.siswa.level({
                siswa: siswaId,
                kompetensi: baris.kompetensi_id,
            }).url,
            { level_siswa: level },
            {
                preserveScroll: true,
                onStart: () => setMenyimpan(level),
                onFinish: () => setMenyimpan(null),
            },
        );
    };

    return (
        <div>
            <div className="text-xs font-semibold text-navy-600">
                Isi level siswa
            </div>
            <div
                className="mt-2 grid grid-cols-4 gap-1.5"
                role="radiogroup"
                aria-label={`Level ${baris.nama_kompetensi_sekolah}`}
            >
                {[1, 2, 3, 4].map((n) => (
                    <button
                        key={n}
                        type="button"
                        role="radio"
                        aria-checked={levelAktif === n}
                        title={namaLevel(n)}
                        disabled={menyimpan !== null}
                        onClick={() => simpan(n)}
                        className={cn(
                            'relative h-10 rounded-lg border text-sm font-bold transition-colors disabled:opacity-60',
                            levelAktif === n
                                ? 'border-navy-900 bg-navy-900 text-white'
                                : 'bg-card text-navy-800 hover:bg-navy-100',
                            menyimpan === n && 'animate-pulse',
                        )}
                    >
                        {n}
                        {n === baris.target_level && (
                            <span className="absolute -top-1.5 right-1 rounded bg-hijau-500 px-1 text-[9px] leading-tight font-bold text-white">
                                target
                            </span>
                        )}
                    </button>
                ))}
            </div>
            <p className="mt-2 text-xs text-muted-foreground">
                {levelAktif
                    ? namaLevel(levelAktif)
                    : 'Belum diisi (dianggap level 1)'}
            </p>
        </div>
    );
}
