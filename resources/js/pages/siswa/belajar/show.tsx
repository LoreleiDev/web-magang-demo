import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    ArrowLeft,
    Check,
    CheckCircle2,
    ChevronLeft,
    ChevronRight,
    CircleX,
    LoaderCircle,
    RotateCcw,
    TrendingUp,
} from 'lucide-react';
import { useEffect, useState } from 'react';
import { BadgeVerifikasi } from '@/components/badge-verifikasi';
import { MediaEmbed } from '@/components/media-embed';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import siswa from '@/routes/siswa';
import type { MateriDetail, MateriRingkas } from '@/types/materi';
import type { HasilKuis, StatusKuis } from '@/types/siswa';

type Props = {
    materi: MateriDetail;
    statusKuis: StatusKuis;
    levelSiswa: number;
    targetLevel: number;
    materiLain: MateriRingkas[];
};

const INDEKS_KUIS = 5;

/**
 * Microlearning 8 langkah dengan stepper (bagian 6.4) dan kuis (bagian 6.7).
 */
export default function BelajarShow({
    materi,
    statusKuis,
    levelSiswa,
    targetLevel,
    materiLain,
}: Props) {
    const { flash } = usePage();
    const hasilFlash =
        flash.hasilKuis?.materi_id === materi.id ? flash.hasilKuis : null;

    const [aktif, setAktif] = useState(hasilFlash ? INDEKS_KUIS : 0);
    const [dikunjungi, setDikunjungi] = useState<Set<number>>(
        () => new Set([hasilFlash ? INDEKS_KUIS : 0]),
    );
    const [hasil, setHasil] = useState<HasilKuis | null>(hasilFlash);

    useEffect(() => {
        if (hasilFlash) {
            setHasil(hasilFlash);
            setAktif(INDEKS_KUIS);
        }
    }, [hasilFlash]);

    const pindah = (i: number) => {
        setAktif(i);
        setDikunjungi((s) => new Set(s).add(i));
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    const langkah = materi.langkah[aktif];

    return (
        <>
            <Head title={materi.judul} />
            <Link
                href={siswa.belajar.index().url}
                className="mb-4 inline-flex items-center gap-1.5 text-sm font-semibold text-navy-600 hover:text-navy-900"
            >
                <ArrowLeft className="size-4" /> Semua materi
            </Link>

            <header className="mb-5">
                <p className="text-xs font-bold tracking-[0.16em] text-hijau-600 uppercase">
                    {materi.kompetensi} · Level {materi.level}
                </p>
                <h1 className="mt-1.5 text-2xl font-extrabold tracking-tight text-balance text-navy-900 sm:text-3xl">
                    {materi.judul}
                </h1>
                <BadgeVerifikasi
                    terverifikasi={materi.terverifikasi_industri}
                    className="mt-3"
                />
                <p className="mt-2 text-sm text-muted-foreground">
                    Level Anda di kompetensi ini: <strong>{levelSiswa}</strong>{' '}
                    (target {targetLevel}).{' '}
                    {statusKuis.lulus
                        ? 'Anda sudah lulus kuis materi ini.'
                        : `Lulus kuis (≥ ${materi.nilai_minimal}) untuk mencapai level ${materi.level}.`}
                </p>
            </header>

            {/* Stepper 8 langkah */}
            <nav
                aria-label="Langkah materi"
                className="sticky top-14 z-10 -mx-4 mb-5 border-b bg-background/95 px-4 py-2 backdrop-blur lg:top-0"
            >
                <ol className="flex gap-1 overflow-x-auto">
                    {materi.langkah.map((l, i) => {
                        const selesai = dikunjungi.has(i) && i !== aktif;

                        return (
                            <li key={l.urutan} className="shrink-0">
                                <button
                                    type="button"
                                    onClick={() => pindah(i)}
                                    aria-current={
                                        aktif === i ? 'step' : undefined
                                    }
                                    className={cn(
                                        'flex items-center gap-2 rounded-xl px-2.5 py-2 text-xs font-semibold transition-colors',
                                        aktif === i
                                            ? 'bg-navy-900 text-white'
                                            : 'text-navy-700 hover:bg-navy-50',
                                    )}
                                >
                                    <span
                                        className={cn(
                                            'grid size-6 place-items-center rounded-lg text-[11px] font-extrabold',
                                            aktif === i
                                                ? 'bg-white/15'
                                                : selesai
                                                  ? 'bg-hijau-500 text-white'
                                                  : 'bg-navy-100',
                                        )}
                                    >
                                        {selesai ? (
                                            <Check className="size-3.5" />
                                        ) : (
                                            l.urutan
                                        )}
                                    </span>
                                    <span
                                        className={cn(
                                            aktif !== i && 'hidden sm:inline',
                                        )}
                                    >
                                        {l.judul}
                                    </span>
                                </button>
                            </li>
                        );
                    })}
                </ol>
                <div className="mt-2 h-1 overflow-hidden rounded-full bg-navy-100">
                    <div
                        className="h-full rounded-full bg-hijau-500 transition-all"
                        style={{
                            width: `${((aktif + 1) / materi.langkah.length) * 100}%`,
                        }}
                    />
                </div>
            </nav>

            <article className="rounded-3xl border bg-card p-2 shadow-xs">
                <div className="px-3 pt-2 pb-3">
                    <p className="text-xs font-semibold text-navy-500">
                        Langkah {langkah.urutan} dari {materi.langkah.length}
                    </p>
                    <h2 className="text-xl font-extrabold text-navy-900">
                        {langkah.judul}
                    </h2>
                </div>
                <div className="space-y-4 rounded-2xl bg-navy-50/60 p-4 sm:p-5">
                    {langkah.konten_teks && (
                        <p className="text-[15px] leading-relaxed whitespace-pre-line text-navy-900">
                            {langkah.konten_teks}
                        </p>
                    )}
                    {langkah.media.map((m, i) => (
                        <MediaEmbed key={i} media={m} />
                    ))}
                    {aktif === INDEKS_KUIS &&
                        (hasil ? (
                            <HasilKuisPanel
                                hasil={hasil}
                                materi={materi}
                                materiLain={materiLain}
                                ulangi={() => setHasil(null)}
                                keLangkah={pindah}
                            />
                        ) : (
                            <FormKuis materi={materi} />
                        ))}
                </div>
            </article>

            <div className="mt-4 flex justify-between gap-2">
                <Button
                    variant="outline"
                    disabled={aktif === 0}
                    onClick={() => pindah(aktif - 1)}
                >
                    <ChevronLeft />
                    Sebelumnya
                </Button>
                {aktif < materi.langkah.length - 1 ? (
                    <Button onClick={() => pindah(aktif + 1)}>
                        Berikutnya
                        <ChevronRight />
                    </Button>
                ) : (
                    <Button asChild variant="aksen">
                        <Link href={siswa.logbook.index().url}>
                            Tulis refleksi di logbook
                            <ChevronRight />
                        </Link>
                    </Button>
                )}
            </div>
        </>
    );
}

function FormKuis({ materi }: { materi: MateriDetail }) {
    const [jawaban, setJawaban] = useState<Record<number, number>>({});
    const [mengirim, setMengirim] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const lengkap = materi.soal.every((s) => jawaban[s.id] !== undefined);

    const kirim = () => {
        router.post(
            siswa.belajar.kuis(materi.id).url,
            { jawaban },
            {
                preserveScroll: true,
                onStart: () => setMengirim(true),
                onFinish: () => setMengirim(false),
                onError: () =>
                    setError(
                        'Jawab semua soal terlebih dahulu, lalu kirim lagi.',
                    ),
            },
        );
    };

    if (materi.soal.length === 0) {
        return (
            <p className="text-sm text-muted-foreground italic">
                Kuis untuk materi ini belum tersedia.
            </p>
        );
    }

    return (
        <div className="space-y-3">
            {materi.soal.map((s, i) => (
                <fieldset key={s.id} className="rounded-2xl border bg-card p-4">
                    <legend className="sr-only">Soal {i + 1}</legend>
                    <p className="text-sm font-semibold text-navy-900">
                        {i + 1}. {s.pertanyaan}
                    </p>
                    <div className="mt-3 grid gap-1.5">
                        {s.pilihan.map((p, j) => {
                            const dipilih = jawaban[s.id] === j;

                            return (
                                <label
                                    key={j}
                                    className={cn(
                                        'flex cursor-pointer items-start gap-3 rounded-xl border px-3 py-2.5 text-sm transition-colors',
                                        dipilih
                                            ? 'border-navy-900 bg-navy-50 font-semibold text-navy-900'
                                            : 'text-navy-800 hover:bg-navy-50/60',
                                    )}
                                >
                                    <input
                                        type="radio"
                                        name={`soal-${s.id}`}
                                        className="mt-0.5 accent-navy-900"
                                        checked={dipilih}
                                        onChange={() =>
                                            setJawaban((x) => ({
                                                ...x,
                                                [s.id]: j,
                                            }))
                                        }
                                    />
                                    <span className="font-bold">
                                        {String.fromCharCode(65 + j)}.
                                    </span>
                                    <span className="flex-1">{p}</span>
                                </label>
                            );
                        })}
                    </div>
                </fieldset>
            ))}
            {error && (
                <p className="text-sm font-medium text-destructive">{error}</p>
            )}
            <Button
                className="w-full"
                size="lg"
                disabled={!lengkap || mengirim}
                onClick={kirim}
            >
                {mengirim && <LoaderCircle className="animate-spin" />}
                {lengkap
                    ? 'Kirim jawaban'
                    : `Jawab semua soal (${Object.keys(jawaban).length}/${materi.soal.length})`}
            </Button>
        </div>
    );
}

function HasilKuisPanel({
    hasil,
    materi,
    materiLain,
    ulangi,
    keLangkah,
}: {
    hasil: HasilKuis;
    materi: MateriDetail;
    materiLain: MateriRingkas[];
    ulangi: () => void;
    keLangkah: (i: number) => void;
}) {
    return (
        <div className="space-y-4">
            <div
                className={cn(
                    'rounded-2xl p-5',
                    hasil.lulus ? 'bg-hijau-500 text-white' : 'border bg-card',
                )}
            >
                <div className="flex items-center gap-3">
                    {hasil.lulus ? (
                        <CheckCircle2 className="size-8 shrink-0" />
                    ) : (
                        <CircleX className="size-8 shrink-0 text-gap-tinggi" />
                    )}
                    <div>
                        <p
                            className={cn(
                                'text-sm font-semibold',
                                !hasil.lulus && 'text-navy-700',
                            )}
                        >
                            {hasil.lulus ? 'Lulus' : 'Belum lulus'} ·{' '}
                            {hasil.benar} dari {hasil.total} benar
                        </p>
                        <p
                            className={cn(
                                'text-4xl font-extrabold tabular-nums',
                                !hasil.lulus && 'text-navy-900',
                            )}
                        >
                            {hasil.skor}
                        </p>
                    </div>
                </div>
                {hasil.level_naik && (
                    <p className="mt-3 flex items-center gap-2 rounded-xl bg-white/15 px-3 py-2 text-sm font-semibold">
                        <TrendingUp className="size-4" />
                        Level kompetensi Anda naik ke level {hasil.level_naik}!
                    </p>
                )}
            </div>

            {!hasil.lulus && (
                <div className="rounded-2xl border border-gap-sedang/40 bg-gap-sedang-soft p-4 text-sm text-navy-900">
                    <p className="font-bold">Rekomendasi penguatan</p>
                    <p className="mt-1">
                        Skor minimal {hasil.nilai_minimal}. Pelajari lagi bagian
                        berikut, lalu coba kuis kembali:
                    </p>
                    <div className="mt-3 flex flex-wrap gap-2">
                        <Button
                            size="sm"
                            variant="outline"
                            className="bg-card"
                            onClick={() => keLangkah(0)}
                        >
                            Konsep Dasar
                        </Button>
                        <Button
                            size="sm"
                            variant="outline"
                            className="bg-card"
                            onClick={() => keLangkah(4)}
                        >
                            Latihan
                        </Button>
                        {materiLain.map((m) => (
                            <Button
                                key={m.id}
                                size="sm"
                                variant="outline"
                                className="bg-card"
                                asChild
                            >
                                <Link href={siswa.belajar.show(m.id).url}>
                                    {m.judul}
                                </Link>
                            </Button>
                        ))}
                    </div>
                </div>
            )}

            <ol className="space-y-2">
                {materi.soal.map((s, i) => {
                    const r = hasil.rincian.find((x) => x.soal_id === s.id);

                    return (
                        <li
                            key={s.id}
                            className="rounded-2xl border bg-card p-4"
                        >
                            <p className="flex gap-2 text-sm font-semibold text-navy-900">
                                {r?.benar ? (
                                    <CheckCircle2 className="mt-0.5 size-4 shrink-0 text-hijau-600" />
                                ) : (
                                    <CircleX className="mt-0.5 size-4 shrink-0 text-gap-tinggi" />
                                )}
                                {i + 1}. {s.pertanyaan}
                            </p>
                            <p className="mt-2 text-sm text-navy-700">
                                Jawaban Anda:{' '}
                                <strong>
                                    {r?.dipilih !== null &&
                                    r?.dipilih !== undefined
                                        ? s.pilihan[r.dipilih]
                                        : '-'}
                                </strong>
                                {!r?.benar && r && (
                                    <>
                                        {' '}
                                        · Jawaban benar:{' '}
                                        <strong className="text-hijau-700">
                                            {s.pilihan[r.jawaban_benar]}
                                        </strong>
                                    </>
                                )}
                            </p>
                            {r?.pembahasan && (
                                <p className="mt-1.5 text-xs leading-relaxed text-muted-foreground">
                                    <span className="font-semibold text-navy-700">
                                        Pembahasan:
                                    </span>{' '}
                                    {r.pembahasan}
                                </p>
                            )}
                        </li>
                    );
                })}
            </ol>

            <Button variant="outline" className="bg-card" onClick={ulangi}>
                <RotateCcw />
                Ulangi kuis
            </Button>
        </div>
    );
}
