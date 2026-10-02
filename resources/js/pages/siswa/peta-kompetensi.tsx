import { Head } from '@inertiajs/react';
import { router } from '@inertiajs/react';
import { Factory, School, Star, Target } from 'lucide-react';
import { Button } from '@/components/ui/button';
import siswa from '@/routes/siswa';
import { EmptyState } from '@/components/empty-state';
import {
    GapBadge,
    LevelBar,
    namaLevel,
    StatusBadge,
} from '@/components/kompetensi-indikator';
import { PageHeader } from '@/components/page-header';
import type { BarisKompetensi } from '@/types/kompetensi';

const legenda = [
    { warna: 'bg-gap-tinggi', label: 'Merah', arti: 'Gap tinggi (≥ 2)' },
    { warna: 'bg-gap-sedang', label: 'Kuning', arti: 'Perlu penguatan (1)' },
    { warna: 'bg-gap-aman', label: 'Hijau', arti: 'Sesuai target' },
];

/**
 * Peta Kompetensi (bagian 6.2): kompetensi sekolah vs aktivitas industri.
 * Tabel di layar lebar, daftar card di smartphone.
 */
export default function PetaKompetensi({
    kompetensi,
    kompetensiUtamaId,
}: {
    kompetensi: BarisKompetensi[];
    kompetensiUtamaId: number | null;
}) {
    return (
        <>
            <Head title="Peta Kompetensi" />
            <PageHeader
                eyebrow="Peta kompetensi"
                title="Sekolah vs industri"
                description="Perbandingan kompetensi yang Anda pelajari di sekolah dengan aktivitas di tempat magang. Level naik otomatis saat Anda lulus kuis materi."
            />

            <div className="mb-5 flex flex-wrap gap-x-5 gap-y-2 text-xs text-navy-700">
                {legenda.map((l) => (
                    <span key={l.label} className="flex items-center gap-1.5">
                        <span className={`size-2.5 rounded-full ${l.warna}`} />
                        <strong>{l.label}</strong> {l.arti}
                    </span>
                ))}
            </div>

            {kompetensi.length === 0 ? (
                <EmptyState
                    icon={Target}
                    title="Belum ada kompetensi"
                    description="Guru belum membuat kompetensi untuk program keahlian Anda."
                />
            ) : (
                <>
                    {/* Smartphone & tablet: card */}
                    <ul className="space-y-3 lg:hidden">
                        {kompetensi.map((k) => (
                            <li
                                key={k.kompetensi_id}
                                className="rounded-3xl border bg-card p-4 shadow-xs"
                            >
                                <div className="flex flex-wrap gap-1.5">
                                    <GapBadge warna={k.warna} gap={k.gap} />
                                    <StatusBadge
                                        status={k.status}
                                        label={k.status_label}
                                    />
                                </div>
                                <div className="mt-3 grid gap-2">
                                    <div className="rounded-2xl bg-navy-50/70 p-3">
                                        <div className="flex items-center gap-1.5 text-[11px] font-bold text-navy-500 uppercase">
                                            <School className="size-3.5" />{' '}
                                            Sekolah
                                        </div>
                                        <div className="mt-0.5 font-bold text-navy-900">
                                            {k.nama_kompetensi_sekolah}
                                        </div>
                                    </div>
                                    <div className="rounded-2xl border border-dashed p-3">
                                        <div className="flex items-center gap-1.5 text-[11px] font-bold text-navy-500 uppercase">
                                            <Factory className="size-3.5" />{' '}
                                            Industri
                                        </div>
                                        <div className="mt-0.5 text-sm text-navy-800">
                                            {k.aktivitas_kompetensi_industri}
                                        </div>
                                    </div>
                                </div>
                                <div className="mt-3">
                                    <LevelBar
                                        level={k.level}
                                        target={k.target_level}
                                        warna={k.warna}
                                    />
                                    <p className="mt-1.5 text-xs text-navy-600">
                                        Level {k.level} · {namaLevel(k.level)} ·
                                        target {k.target_level}
                                    </p>
                                </div>
                                <div className="mt-3">
                                    <TombolUtama
                                        kompetensiId={k.kompetensi_id}
                                        utama={
                                            k.kompetensi_id ===
                                            kompetensiUtamaId
                                        }
                                    />
                                </div>
                            </li>
                        ))}
                    </ul>

                    {/* Desktop: tabel */}
                    <div className="hidden overflow-hidden rounded-3xl border bg-card shadow-xs lg:block">
                        <table className="w-full text-left text-sm">
                            <thead className="bg-navy-50/70 text-xs font-bold text-navy-600">
                                <tr>
                                    <th className="px-5 py-3">
                                        Kompetensi sekolah
                                    </th>
                                    <th className="px-5 py-3">
                                        Aktivitas / kompetensi industri
                                    </th>
                                    <th className="w-48 px-5 py-3">Level</th>
                                    <th className="px-5 py-3">Gap</th>
                                    <th className="px-5 py-3">Status</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {kompetensi.map((k) => (
                                    <tr
                                        key={k.kompetensi_id}
                                        className="align-top"
                                    >
                                        <td className="px-5 py-4 font-bold text-navy-900">
                                            {k.nama_kompetensi_sekolah}
                                            <div className="mt-2 font-normal">
                                                <TombolUtama
                                                    kompetensiId={
                                                        k.kompetensi_id
                                                    }
                                                    utama={
                                                        k.kompetensi_id ===
                                                        kompetensiUtamaId
                                                    }
                                                />
                                            </div>
                                        </td>
                                        <td className="px-5 py-4 text-navy-700">
                                            {k.aktivitas_kompetensi_industri}
                                        </td>
                                        <td className="px-5 py-4">
                                            <LevelBar
                                                level={k.level}
                                                target={k.target_level}
                                                warna={k.warna}
                                            />
                                            <p className="mt-1.5 text-xs text-navy-600">
                                                {k.level} dari target{' '}
                                                {k.target_level}
                                            </p>
                                        </td>
                                        <td className="px-5 py-4">
                                            <GapBadge
                                                warna={k.warna}
                                                gap={k.gap}
                                            />
                                        </td>
                                        <td className="px-5 py-4">
                                            <StatusBadge
                                                status={k.status}
                                                label={k.status_label}
                                            />
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </>
            )}
        </>
    );
}

/**
 * Kompetensi utama siswa bisa diganti kapan saja (keputusan 13 no. 30).
 */
function TombolUtama({
    kompetensiId,
    utama,
}: {
    kompetensiId: number;
    utama: boolean;
}) {
    if (utama) {
        return (
            <span className="inline-flex items-center gap-1.5 rounded-full bg-hijau-50 px-2.5 py-1 text-xs font-bold text-hijau-700">
                <Star className="size-3.5 fill-hijau-500 text-hijau-500" />
                Kompetensi utama
            </span>
        );
    }

    return (
        <Button
            variant="outline"
            size="sm"
            onClick={() =>
                router.put(
                    siswa.kompetensiUtama().url,
                    { kompetensi_id: kompetensiId },
                    { preserveScroll: true },
                )
            }
        >
            <Star />
            Jadikan kompetensi utama
        </Button>
    );
}
