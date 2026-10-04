import {
    BadgeCheck,
    Building2,
    CalendarRange,
    Factory,
    MapPin,
} from 'lucide-react';
import type { ReactNode } from 'react';
import { EmptyState } from '@/components/empty-state';
import {
    GapBadge,
    LevelBar,
    namaLevel,
    StatusBadge,
} from '@/components/kompetensi-indikator';
import { LogbookKartu } from '@/components/logbook-kartu';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { formatPeriode, formatTanggal, formatTanggalWaktu } from '@/lib/format';
import { cn } from '@/lib/utils';
import type {
    BarisKompetensi,
    HasilAssessment,
    Logbook,
    RingkasanSiswa,
} from '@/types/kompetensi';
import { ClipboardCheck, NotebookPen } from 'lucide-react';

export type DataDetailSiswa = {
    siswa: RingkasanSiswa & {
        email: string;
        kelompok: string | null;
        perusahaan: string | null;
        periode_mulai: string | null;
        periode_selesai: string | null;
    };
    kompetensi: BarisKompetensi[];
    logbook: Logbook[];
    assessment: HasilAssessment[];
};

/**
 * Detail pemantauan satu siswa untuk guru/industri. `aksiKompetensi` mengisi
 * area aksi per kompetensi (guru: isi level; industri: verifikasi).
 */
export function DetailSiswa({
    data,
    aksiKompetensi,
    aksiUnitKerja,
}: {
    data: DataDetailSiswa;
    aksiKompetensi?: (baris: BarisKompetensi) => ReactNode;
    /** Tombol ganti unit kerja (khusus industri). */
    aksiUnitKerja?: ReactNode;
}) {
    const { siswa, kompetensi, logbook, assessment } = data;

    return (
        <>
            <header className="mb-6">
                <p className="text-xs font-bold tracking-[0.16em] text-hijau-600 uppercase">
                    {siswa.program_keahlian} · NIS {siswa.id_siswa}
                </p>
                <h1 className="mt-1.5 text-2xl font-extrabold tracking-tight text-navy-900 sm:text-3xl">
                    {siswa.nama}
                </h1>
                <div className="mt-3 flex flex-wrap gap-x-5 gap-y-1.5 text-sm text-navy-700">
                    {siswa.perusahaan && (
                        <span className="flex items-center gap-1.5">
                            <Building2 className="size-4 text-navy-400" />
                            {siswa.perusahaan}
                        </span>
                    )}
                    <span className="flex items-center gap-1.5">
                        <MapPin className="size-4 text-navy-400" />
                        {siswa.unit_kerja ?? 'Belum memilih unit kerja'}
                        {aksiUnitKerja}
                    </span>
                    {siswa.periode_mulai && siswa.periode_selesai && (
                        <span className="flex items-center gap-1.5">
                            <CalendarRange className="size-4 text-navy-400" />
                            {formatPeriode(
                                siswa.periode_mulai,
                                siswa.periode_selesai,
                            )}
                        </span>
                    )}
                </div>
            </header>

            <div className="mb-8 grid grid-cols-2 gap-3 lg:grid-cols-4">
                <Angka
                    label="Progres"
                    nilai={`${siswa.progres.persen}%`}
                    ket={`${siswa.progres.dikuasai}/${siswa.progres.total} sesuai target`}
                    tonjol
                />
                <Angka
                    label="Learning gap"
                    nilai={siswa.progres.gap}
                    ket="kompetensi di bawah target"
                />
                <Angka
                    label="Terverifikasi industri"
                    nilai={siswa.progres.terverifikasi}
                    ket="kompetensi"
                />
                <Angka
                    label="Rata-rata skor"
                    nilai={siswa.rata_skor ?? '-'}
                    ket={`${assessment.length} percobaan kuis`}
                />
            </div>

            <section className="mb-10">
                <h2 className="mb-1 text-lg font-bold text-navy-900">
                    Peta kompetensi
                </h2>
                <p className="mb-4 text-sm text-muted-foreground">
                    Level naik otomatis saat siswa lulus kuis materi berlevel.
                    Level awal 1 (Belum mampu).
                </p>

                {kompetensi.length === 0 ? (
                    <EmptyState
                        icon={Factory}
                        title="Belum ada kompetensi"
                        description="Kompetensi untuk program keahlian ini belum dibuat guru."
                    />
                ) : (
                    <ul className="space-y-3">
                        {kompetensi.map((k) => (
                            <li
                                key={k.kompetensi_id}
                                className="rounded-3xl border bg-card p-2 shadow-xs"
                            >
                                <div className="grid gap-4 p-3 lg:grid-cols-[1fr_auto] lg:items-center">
                                    <div className="min-w-0">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <StatusBadge
                                                status={k.status}
                                                label={k.status_label}
                                            />
                                            <GapBadge
                                                warna={k.warna}
                                                gap={k.gap}
                                            />
                                        </div>
                                        <h3 className="mt-2 font-bold text-navy-900">
                                            {k.nama_kompetensi_sekolah}
                                        </h3>
                                        <p className="mt-0.5 flex gap-1.5 text-sm text-muted-foreground">
                                            <Factory className="mt-0.5 size-4 shrink-0 text-navy-300" />
                                            {k.aktivitas_kompetensi_industri}
                                        </p>
                                        <div className="mt-3 max-w-xs">
                                            <LevelBar
                                                level={k.level}
                                                target={k.target_level}
                                                warna={k.warna}
                                            />
                                            <p className="mt-1.5 text-xs text-navy-600">
                                                Level <strong>{k.level}</strong>{' '}
                                                · {namaLevel(k.level)}
                                                {!k.level_diisi &&
                                                    ' (belum lulus kuis)'}{' '}
                                                · target {k.target_level}
                                            </p>
                                        </div>
                                        {k.status === 'terverifikasi' && (
                                            <p className="mt-2 flex items-center gap-1.5 text-xs font-semibold text-hijau-700">
                                                <BadgeCheck className="size-4" />
                                                Diverifikasi{' '}
                                                {k.diverifikasi_oleh} ·{' '}
                                                {formatTanggal(
                                                    k.diverifikasi_pada,
                                                )}
                                            </p>
                                        )}
                                    </div>
                                    {aksiKompetensi && (
                                        <div className="rounded-2xl bg-navy-50/70 p-3 lg:min-w-64">
                                            {aksiKompetensi(k)}
                                        </div>
                                    )}
                                </div>
                            </li>
                        ))}
                    </ul>
                )}
            </section>

            <Tabs defaultValue="logbook">
                <TabsList className="mb-4">
                    <TabsTrigger value="logbook">
                        Logbook ({logbook.length})
                    </TabsTrigger>
                    <TabsTrigger value="assessment">
                        Assessment ({assessment.length})
                    </TabsTrigger>
                </TabsList>
                <TabsContent value="logbook">
                    {logbook.length === 0 ? (
                        <EmptyState
                            icon={NotebookPen}
                            title="Belum ada logbook"
                        />
                    ) : (
                        <ul className="space-y-3">
                            {logbook.map((l) => (
                                <li key={l.id}>
                                    <LogbookKartu
                                        logbook={l}
                                        tampilkanSiswa={false}
                                    />
                                </li>
                            ))}
                        </ul>
                    )}
                </TabsContent>
                <TabsContent value="assessment">
                    {assessment.length === 0 ? (
                        <EmptyState
                            icon={ClipboardCheck}
                            title="Belum mengerjakan kuis"
                        />
                    ) : (
                        <ul className="overflow-hidden rounded-3xl border bg-card shadow-xs">
                            {assessment.map((h) => (
                                <li
                                    key={h.id}
                                    className="flex items-center gap-4 border-b px-5 py-3.5 last:border-none"
                                >
                                    <div className="min-w-0 flex-1">
                                        <div className="truncate text-sm font-semibold text-navy-900">
                                            {h.materi}
                                        </div>
                                        <div className="text-xs text-muted-foreground">
                                            {formatTanggalWaktu(h.tanggal)}
                                        </div>
                                    </div>
                                    <span className="text-lg font-extrabold text-navy-900 tabular-nums">
                                        {h.skor}
                                    </span>
                                    <span
                                        className={cn(
                                            'rounded-full px-2 py-0.5 text-[11px] font-bold',
                                            h.lulus
                                                ? 'bg-hijau-50 text-hijau-700'
                                                : 'bg-gap-tinggi-soft text-gap-tinggi',
                                        )}
                                    >
                                        {h.lulus ? 'Lulus' : 'Belum lulus'}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    )}
                </TabsContent>
            </Tabs>
        </>
    );
}

function Angka({
    label,
    nilai,
    ket,
    tonjol = false,
}: {
    label: string;
    nilai: number | string;
    ket: string;
    tonjol?: boolean;
}) {
    return (
        <div
            className={cn(
                'rounded-3xl p-4 sm:p-5',
                tonjol
                    ? 'bg-navy-900 text-white shadow-md'
                    : 'border bg-card shadow-xs',
            )}
        >
            <div
                className={cn(
                    'text-xs font-semibold',
                    tonjol ? 'text-navy-200' : 'text-navy-500',
                )}
            >
                {label}
            </div>
            <div
                className={cn(
                    'mt-1 text-3xl font-extrabold tabular-nums',
                    !tonjol && 'text-navy-900',
                )}
            >
                {nilai}
            </div>
            <div
                className={cn(
                    'text-xs',
                    tonjol ? 'text-navy-300' : 'text-muted-foreground',
                )}
            >
                {ket}
            </div>
        </div>
    );
}
