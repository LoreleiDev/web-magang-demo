import { Head, Link } from '@inertiajs/react';
import { Star, UsersRound } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import { PageHeader } from '@/components/page-header';
import { cn } from '@/lib/utils';
import guru from '@/routes/guru';
import type {
    RingkasanProgres,
    StatusKompetensi,
    WarnaGap,
} from '@/types/kompetensi';

type Sel = {
    level: number;
    target: number;
    gap: number;
    warna: WarnaGap;
    status: StatusKompetensi;
};

type BarisSiswa = {
    id: number;
    nama: string;
    kompetensi_utama_id: number | null;
    ringkasan: RingkasanProgres;
    sel: Record<number, Sel>;
};

type Program = {
    kode: string;
    kompetensi: { id: number; nama: string; target: number }[];
    siswa: BarisSiswa[];
};

type Props = {
    kelompok: { id: number; nama_kelompok: string; perusahaan: string }[];
    kelompokTerpilih: number | null;
    program: Program[];
};

const gayaSel: Record<WarnaGap, string> = {
    tinggi: 'bg-gap-tinggi-soft text-gap-tinggi',
    sedang: 'bg-gap-sedang-soft text-navy-900',
    aman: 'bg-gap-aman-soft text-hijau-700',
};

const legenda = [
    { warna: 'bg-gap-tinggi', label: 'Gap tinggi (≥ 2)' },
    { warna: 'bg-gap-sedang', label: 'Perlu penguatan (1)' },
    { warna: 'bg-gap-aman', label: 'Sesuai target' },
];

/**
 * Rekap Kelompok (keputusan 13 no. 31): learning gap dan progres semua siswa.
 * Desktop: tabel siswa × kompetensi. Smartphone: card per siswa.
 */
export default function RekapKelompok({
    kelompok,
    kelompokTerpilih,
    program,
}: Props) {
    return (
        <>
            <Head title="Rekap Kelompok" />
            <PageHeader
                eyebrow="Rekap kelompok"
                title="Learning gap & progres siswa"
                description="Sel menunjukkan level siswa / target. Warna sesuai besarnya gap."
            />

            {kelompok.length === 0 ? (
                <EmptyState
                    icon={UsersRound}
                    title="Belum ada kelompok bimbingan"
                    description="Anda belum ditetapkan sebagai guru pembimbing kelompok magang."
                />
            ) : (
                <>
                    <div className="-mx-4 mb-4 flex gap-2 overflow-x-auto px-4 pb-1 md:mx-0 md:px-0">
                        {kelompok.map((k) => (
                            <Link
                                key={k.id}
                                href={
                                    guru.rekap({ query: { kelompok: k.id } })
                                        .url
                                }
                                preserveScroll
                                className={cn(
                                    'shrink-0 rounded-2xl border px-4 py-2.5 text-sm font-semibold transition-colors',
                                    k.id === kelompokTerpilih
                                        ? 'border-navy-900 bg-navy-900 text-white'
                                        : 'bg-card text-navy-800 hover:bg-navy-50',
                                )}
                            >
                                {k.nama_kelompok}
                            </Link>
                        ))}
                    </div>

                    <div className="mb-5 flex flex-wrap gap-x-5 gap-y-2 text-xs text-navy-700">
                        {legenda.map((l) => (
                            <span
                                key={l.label}
                                className="flex items-center gap-1.5"
                            >
                                <span
                                    className={`size-2.5 rounded-full ${l.warna}`}
                                />
                                {l.label}
                            </span>
                        ))}
                        <span className="flex items-center gap-1.5">
                            <Star className="size-3.5 fill-hijau-500 text-hijau-500" />
                            Kompetensi utama siswa
                        </span>
                    </div>

                    {program.length === 0 && (
                        <EmptyState
                            icon={UsersRound}
                            title="Belum ada siswa di kelompok ini"
                        />
                    )}

                    <div className="space-y-8">
                        {program.map((p) => (
                            <section key={p.kode}>
                                {program.length > 1 && (
                                    <h2 className="mb-3 text-base font-bold text-navy-900">
                                        Program {p.kode}
                                    </h2>
                                )}

                                {/* Smartphone & tablet */}
                                <ul className="space-y-3 lg:hidden">
                                    {p.siswa.map((s) => (
                                        <li
                                            key={s.id}
                                            className="rounded-3xl border bg-card p-4 shadow-xs"
                                        >
                                            <KepalaSiswa siswa={s} />
                                            <ul className="mt-3 space-y-1.5">
                                                {p.kompetensi.map((k) => {
                                                    const sel = s.sel[k.id];

                                                    return (
                                                        <li
                                                            key={k.id}
                                                            className={cn(
                                                                'flex items-center justify-between gap-3 rounded-xl px-3 py-2 text-sm',
                                                                gayaSel[
                                                                    sel.warna
                                                                ],
                                                            )}
                                                        >
                                                            <span className="flex min-w-0 items-center gap-1.5 font-medium">
                                                                {s.kompetensi_utama_id ===
                                                                    k.id && (
                                                                    <Star className="size-3.5 shrink-0 fill-hijau-500 text-hijau-500" />
                                                                )}
                                                                <span className="truncate">
                                                                    {k.nama}
                                                                </span>
                                                            </span>
                                                            <span className="shrink-0 font-bold tabular-nums">
                                                                {sel.level}/
                                                                {sel.target}
                                                            </span>
                                                        </li>
                                                    );
                                                })}
                                            </ul>
                                        </li>
                                    ))}
                                </ul>

                                {/* Desktop */}
                                <div className="hidden overflow-x-auto rounded-3xl border bg-card shadow-xs lg:block">
                                    <table className="w-full text-sm">
                                        <thead className="bg-navy-50/70 text-xs font-bold text-navy-600">
                                            <tr>
                                                <th className="sticky left-0 bg-navy-50 px-4 py-3 text-left">
                                                    Siswa
                                                </th>
                                                {p.kompetensi.map((k) => (
                                                    <th
                                                        key={k.id}
                                                        className="min-w-28 px-2 py-3 text-center font-semibold"
                                                        title={k.nama}
                                                    >
                                                        <span className="line-clamp-2">
                                                            {k.nama}
                                                        </span>
                                                        <span className="block font-normal text-muted-foreground">
                                                            target {k.target}
                                                        </span>
                                                    </th>
                                                ))}
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y">
                                            {p.siswa.map((s) => (
                                                <tr key={s.id}>
                                                    <td className="sticky left-0 min-w-56 bg-card px-4 py-3">
                                                        <KepalaSiswa
                                                            siswa={s}
                                                        />
                                                    </td>
                                                    {p.kompetensi.map((k) => {
                                                        const sel = s.sel[k.id];

                                                        return (
                                                            <td
                                                                key={k.id}
                                                                className="px-2 py-3 text-center"
                                                            >
                                                                <span
                                                                    className={cn(
                                                                        'relative inline-flex min-w-16 items-center justify-center gap-1 rounded-lg px-2 py-1.5 font-bold tabular-nums',
                                                                        gayaSel[
                                                                            sel
                                                                                .warna
                                                                        ],
                                                                    )}
                                                                    title={`Level ${sel.level} dari target ${sel.target}`}
                                                                >
                                                                    {s.kompetensi_utama_id ===
                                                                        k.id && (
                                                                        <Star className="size-3 fill-hijau-500 text-hijau-500" />
                                                                    )}
                                                                    {sel.level}/
                                                                    {sel.target}
                                                                </span>
                                                            </td>
                                                        );
                                                    })}
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            </section>
                        ))}
                    </div>
                </>
            )}
        </>
    );
}

function KepalaSiswa({ siswa }: { siswa: BarisSiswa }) {
    return (
        <div>
            <Link
                href={guru.siswa.show(siswa.id).url}
                className="font-bold text-navy-900 underline-offset-2 hover:underline"
            >
                {siswa.nama}
            </Link>
            <div className="mt-1.5 flex items-center gap-2">
                <div className="h-1.5 w-20 overflow-hidden rounded-full bg-navy-100">
                    <div
                        className="h-full rounded-full bg-hijau-500"
                        style={{ width: `${siswa.ringkasan.persen}%` }}
                    />
                </div>
                <span className="text-xs text-navy-600">
                    {siswa.ringkasan.persen}% · {siswa.ringkasan.gap} gap
                </span>
            </div>
        </div>
    );
}
