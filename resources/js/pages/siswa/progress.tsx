import { Head } from '@inertiajs/react';
import {
    BadgeCheck,
    CheckCircle2,
    CircleX,
    NotebookPen,
    Target,
} from 'lucide-react';
import {
    Bar,
    BarChart,
    CartesianGrid,
    ReferenceLine,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';
import { GapBadge, LevelBar } from '@/components/kompetensi-indikator';
import { PageHeader } from '@/components/page-header';
import { cn } from '@/lib/utils';
import type { BarisKompetensi, RingkasanProgres } from '@/types/kompetensi';

type Skor = {
    materi_id: number;
    judul: string;
    terbaik: number | null;
    nilai_minimal: number;
    lulus: boolean;
};

type Props = {
    ringkasan: RingkasanProgres;
    kompetensi: BarisKompetensi[];
    skor: Skor[];
    logbookPerMinggu: { minggu: string; jumlah: number }[];
    jumlahLogbook: number;
};

// Satu seri per grafik: biru tua (lihat aturan dataviz); teks memakai token teks.
const WARNA_SERI = '#1c3c6b';
const WARNA_GRID = '#dbe5f0';
const WARNA_TEKS = '#56677d';

/**
 * Progress siswa (bagian 6.8).
 */
export default function Progress({
    ringkasan,
    kompetensi,
    skor,
    logbookPerMinggu,
    jumlahLogbook,
}: Props) {
    const rataSkor =
        skor.length === 0
            ? null
            : Math.round(
                  skor.reduce((t, s) => t + (s.terbaik ?? 0), 0) / skor.length,
              );

    return (
        <>
            <Head title="Progress" />
            <PageHeader
                eyebrow="Progress"
                title="Perkembangan Anda"
                description="Ringkasan kemajuan kompetensi, logbook, dan kuis selama magang."
            />

            {/* Angka utama */}
            <div className="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <div className="col-span-2 rounded-3xl bg-navy-900 tekstur-titik p-5 text-white shadow-lg lg:col-span-1">
                    <p className="text-xs font-semibold text-navy-300">
                        Progres keseluruhan
                    </p>
                    <p className="mt-1 text-4xl font-extrabold tabular-nums">
                        {ringkasan.persen}%
                    </p>
                    <div className="mt-3 h-1.5 overflow-hidden rounded-full bg-white/10">
                        <div
                            className="h-full rounded-full bg-hijau-500"
                            style={{ width: `${ringkasan.persen}%` }}
                        />
                    </div>
                </div>
                <Angka
                    icon={Target}
                    label="Learning gap selesai"
                    nilai={`${ringkasan.dikuasai}/${ringkasan.total}`}
                    ket="kompetensi sesuai target"
                />
                <Angka
                    icon={BadgeCheck}
                    label="Diverifikasi industri"
                    nilai={ringkasan.terverifikasi}
                    ket="kompetensi"
                />
                <Angka
                    icon={NotebookPen}
                    label="Jumlah logbook"
                    nilai={jumlahLogbook}
                    ket="logbook ditulis"
                />
            </div>

            {/* Progres per kompetensi */}
            <section className="mt-6 rounded-3xl border bg-card p-5 shadow-xs sm:p-6">
                <h2 className="text-base font-bold text-navy-900">
                    Progres kompetensi
                </h2>
                <p className="text-sm text-muted-foreground">
                    Level Anda dibanding target industri.
                </p>
                <ul className="mt-4 space-y-4">
                    {kompetensi.map((k) => (
                        <li
                            key={k.kompetensi_id}
                            className="grid gap-2 sm:grid-cols-[1fr_12rem_auto] sm:items-center sm:gap-4"
                        >
                            <span className="text-sm font-semibold text-navy-900">
                                {k.nama_kompetensi_sekolah}
                            </span>
                            <div>
                                <LevelBar
                                    level={k.level}
                                    target={k.target_level}
                                    warna={k.warna}
                                />
                                <p className="mt-1 text-xs text-navy-600">
                                    Level {k.level} / target {k.target_level}
                                </p>
                            </div>
                            <GapBadge warna={k.warna} gap={k.gap} />
                        </li>
                    ))}
                </ul>
            </section>

            <div className="mt-6 grid gap-6 lg:grid-cols-2">
                {/* Skor kuis */}
                <section className="min-w-0 rounded-3xl border bg-card p-5 shadow-xs sm:p-6">
                    <h2 className="text-base font-bold text-navy-900">
                        Skor assessment
                    </h2>
                    <p className="text-sm text-muted-foreground">
                        Skor terbaik per materi. Garis putus-putus = nilai
                        minimal.
                        {rataSkor !== null && ` Rata-rata: ${rataSkor}.`}
                    </p>
                    {skor.length === 0 ? (
                        <p className="mt-6 text-sm text-muted-foreground">
                            Belum ada kuis yang dikerjakan.
                        </p>
                    ) : (
                        <>
                            <div
                                className="mt-4 h-56"
                                role="img"
                                aria-label="Grafik skor terbaik per materi"
                            >
                                <ResponsiveContainer width="100%" height="100%">
                                    <BarChart
                                        data={skor.map((s, i) => ({
                                            ...s,
                                            label: `M${i + 1}`,
                                        }))}
                                        margin={{
                                            top: 8,
                                            right: 8,
                                            left: -20,
                                            bottom: 0,
                                        }}
                                    >
                                        <CartesianGrid
                                            vertical={false}
                                            stroke={WARNA_GRID}
                                        />
                                        <XAxis
                                            dataKey="label"
                                            tickLine={false}
                                            axisLine={false}
                                            tick={{
                                                fill: WARNA_TEKS,
                                                fontSize: 12,
                                            }}
                                        />
                                        <YAxis
                                            domain={[0, 100]}
                                            tickLine={false}
                                            axisLine={false}
                                            tick={{
                                                fill: WARNA_TEKS,
                                                fontSize: 12,
                                            }}
                                        />
                                        <Tooltip
                                            cursor={{ fill: '#eef4fb' }}
                                            formatter={(v) => [
                                                String(v ?? '-'),
                                                'Skor terbaik',
                                            ]}
                                            labelFormatter={(_, p) =>
                                                p?.[0]?.payload?.judul ?? ''
                                            }
                                            contentStyle={{
                                                borderRadius: 12,
                                                borderColor: WARNA_GRID,
                                                fontSize: 13,
                                            }}
                                        />
                                        <ReferenceLine
                                            y={skor[0]?.nilai_minimal ?? 75}
                                            stroke={WARNA_TEKS}
                                            strokeDasharray="4 4"
                                        />
                                        <Bar
                                            dataKey="terbaik"
                                            fill={WARNA_SERI}
                                            radius={[4, 4, 0, 0]}
                                            maxBarSize={40}
                                        />
                                    </BarChart>
                                </ResponsiveContainer>
                            </div>
                            {/* Tabel pendamping: identitas tidak bergantung warna */}
                            <ul className="mt-4 space-y-1.5 text-sm">
                                {skor.map((s, i) => (
                                    <li
                                        key={s.materi_id}
                                        className="flex items-center gap-2"
                                    >
                                        <span className="w-8 shrink-0 text-xs font-bold text-navy-500">
                                            M{i + 1}
                                        </span>
                                        <span className="min-w-0 flex-1 truncate text-navy-800">
                                            {s.judul}
                                        </span>
                                        <span className="font-bold text-navy-900 tabular-nums">
                                            {s.terbaik}
                                        </span>
                                        <span
                                            className={cn(
                                                'flex items-center gap-1 text-xs font-semibold',
                                                s.lulus
                                                    ? 'text-hijau-700'
                                                    : 'text-gap-tinggi',
                                            )}
                                        >
                                            {s.lulus ? (
                                                <CheckCircle2 className="size-3.5" />
                                            ) : (
                                                <CircleX className="size-3.5" />
                                            )}
                                            {s.lulus
                                                ? 'Lulus'
                                                : `< ${s.nilai_minimal}`}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        </>
                    )}
                </section>

                {/* Logbook per minggu */}
                <section className="min-w-0 rounded-3xl border bg-card p-5 shadow-xs sm:p-6">
                    <h2 className="text-base font-bold text-navy-900">
                        Logbook per minggu
                    </h2>
                    <p className="text-sm text-muted-foreground">
                        Jumlah logbook di setiap minggu magang.
                    </p>
                    {logbookPerMinggu.length === 0 ? (
                        <p className="mt-6 text-sm text-muted-foreground">
                            Belum ada data.
                        </p>
                    ) : (
                        <div
                            className="mt-4 h-56"
                            role="img"
                            aria-label="Grafik jumlah logbook per minggu"
                        >
                            <ResponsiveContainer width="100%" height="100%">
                                <BarChart
                                    data={logbookPerMinggu}
                                    margin={{
                                        top: 8,
                                        right: 8,
                                        left: -20,
                                        bottom: 0,
                                    }}
                                >
                                    <CartesianGrid
                                        vertical={false}
                                        stroke={WARNA_GRID}
                                    />
                                    <XAxis
                                        dataKey="minggu"
                                        tickLine={false}
                                        axisLine={false}
                                        tick={{
                                            fill: WARNA_TEKS,
                                            fontSize: 12,
                                        }}
                                    />
                                    <YAxis
                                        allowDecimals={false}
                                        domain={[
                                            0,
                                            (maks: number) => Math.max(4, maks),
                                        ]}
                                        tickLine={false}
                                        axisLine={false}
                                        tick={{
                                            fill: WARNA_TEKS,
                                            fontSize: 12,
                                        }}
                                    />
                                    <Tooltip
                                        cursor={{ fill: '#eef4fb' }}
                                        formatter={(v) => [
                                            `${String(v ?? 0)} logbook`,
                                            '',
                                        ]}
                                        labelFormatter={(l) =>
                                            typeof l === 'string'
                                                ? `Minggu ${l.slice(1)}`
                                                : ''
                                        }
                                        contentStyle={{
                                            borderRadius: 12,
                                            borderColor: WARNA_GRID,
                                            fontSize: 13,
                                        }}
                                    />
                                    <Bar
                                        dataKey="jumlah"
                                        fill={WARNA_SERI}
                                        radius={[4, 4, 0, 0]}
                                        maxBarSize={32}
                                    />
                                </BarChart>
                            </ResponsiveContainer>
                        </div>
                    )}
                </section>
            </div>
        </>
    );
}

function Angka({
    icon: Icon,
    label,
    nilai,
    ket,
}: {
    icon: typeof Target;
    label: string;
    nilai: number | string;
    ket: string;
}) {
    return (
        <div className="rounded-3xl border bg-card p-4 shadow-xs sm:p-5">
            <Icon className="size-5 text-navy-400" />
            <p className="mt-2 text-xs font-semibold text-navy-500">{label}</p>
            <p className="text-2xl font-extrabold text-navy-900 tabular-nums sm:text-3xl">
                {nilai}
            </p>
            <p className="text-xs text-muted-foreground">{ket}</p>
        </div>
    );
}
