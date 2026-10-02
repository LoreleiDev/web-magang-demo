import {
    CircleHelp,
    Lightbulb,
    Sparkles,
    Target,
    TriangleAlert,
    Wrench,
} from 'lucide-react';
import { cn } from '@/lib/utils';

export type HasilAnalisis = {
    kompetensi_digunakan: string[];
    kompetensi_baru: string[];
    kemungkinan_learning_gap: string[];
    rekomendasi_materi: string[];
    pertanyaan_refleksi: string[];
};

const bagian: {
    kunci: keyof HasilAnalisis;
    judul: string;
    icon: typeof Sparkles;
    kelas: string;
}[] = [
    {
        kunci: 'kompetensi_digunakan',
        judul: 'Kompetensi yang digunakan',
        icon: Wrench,
        kelas: 'bg-navy-50/70',
    },
    {
        kunci: 'kompetensi_baru',
        judul: 'Kompetensi baru yang ditemukan',
        icon: Lightbulb,
        kelas: 'bg-hijau-50',
    },
    {
        kunci: 'kemungkinan_learning_gap',
        judul: 'Kemungkinan learning gap',
        icon: TriangleAlert,
        kelas: 'bg-gap-sedang-soft',
    },
    {
        kunci: 'rekomendasi_materi',
        judul: 'Rekomendasi materi',
        icon: Target,
        kelas: 'bg-navy-50/70',
    },
    {
        kunci: 'pertanyaan_refleksi',
        judul: 'Pertanyaan refleksi',
        icon: CircleHelp,
        kelas: 'border border-dashed bg-card',
    },
];

/**
 * Hasil "Analisis dengan AI" sebagai card (CLAUDE.md bagian 6.6 & 9.5).
 */
export function HasilAnalisisAi({ hasil }: { hasil: HasilAnalisis }) {
    return (
        <div className="mt-4 rounded-2xl border border-hijau-100 bg-card p-3">
            <p className="mb-2 flex items-center gap-1.5 px-1 text-xs font-bold text-hijau-700">
                <Sparkles className="size-3.5" />
                Analisis AI Mentor
            </p>
            <div className="grid gap-2 sm:grid-cols-2">
                {bagian.map(({ kunci, judul, icon: Icon, kelas }) => (
                    <section
                        key={kunci}
                        className={cn(
                            'rounded-xl p-3',
                            kelas,
                            kunci === 'pertanyaan_refleksi' && 'sm:col-span-2',
                        )}
                    >
                        <h4 className="flex items-center gap-1.5 text-xs font-bold text-navy-700">
                            <Icon className="size-3.5" />
                            {judul}
                        </h4>
                        {hasil[kunci].length === 0 ? (
                            <p className="mt-1 text-sm text-muted-foreground">
                                -
                            </p>
                        ) : (
                            <ul className="mt-1.5 list-disc space-y-1 pl-4 text-sm text-navy-900">
                                {hasil[kunci].map((item, i) => (
                                    <li key={i}>{item}</li>
                                ))}
                            </ul>
                        )}
                    </section>
                ))}
            </div>
        </div>
    );
}
