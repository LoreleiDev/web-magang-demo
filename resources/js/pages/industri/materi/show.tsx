import { Head, Link, useForm } from '@inertiajs/react';
import {
    ArrowLeft,
    BadgeCheck,
    LoaderCircle,
    MessageSquareText,
    PenLine,
} from 'lucide-react';
import type { FormEvent } from 'react';
import { BadgeVerifikasi } from '@/components/badge-verifikasi';
import { MateriBaca } from '@/components/materi-baca';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { formatTanggal, formatTanggalWaktu } from '@/lib/format';
import { cn } from '@/lib/utils';
import industri from '@/routes/industri';
import type { MateriDetail, RiwayatVerifikasi } from '@/types/materi';

type Hasil = 'diverifikasi' | 'belum_sesuai';

/**
 * Baca materi lalu "Verifikasi Materi" (masukan opsional) atau "Belum Sesuai"
 * (masukan wajib) — CLAUDE.md bagian 11.2.
 */
export default function IndustriMateriShow({
    materi,
    riwayatVerifikasi,
}: {
    materi: MateriDetail;
    riwayatVerifikasi: RiwayatVerifikasi[];
}) {
    const form = useForm<{ hasil: Hasil | ''; masukan: string }>({
        hasil: '',
        masukan: '',
    });
    const belumSesuai = form.data.hasil === 'belum_sesuai';

    const kirim = (e: FormEvent) => {
        e.preventDefault();
        form.post(industri.materi.verifikasi(materi.id).url, {
            preserveScroll: true,
        });
    };

    return (
        <>
            <Head title={materi.judul} />
            <Link
                href={industri.materi.index().url}
                className="mb-4 inline-flex items-center gap-1.5 text-sm font-semibold text-navy-600 hover:text-navy-900"
            >
                <ArrowLeft className="size-4" /> Daftar materi
            </Link>

            <header className="mb-8">
                <p className="text-xs font-bold tracking-[0.16em] text-hijau-600 uppercase">
                    {materi.program_keahlian_nama} · Level {materi.level}
                </p>
                <h1 className="mt-1.5 text-2xl font-extrabold tracking-tight text-balance text-navy-900 sm:text-3xl">
                    {materi.judul}
                </h1>
                <BadgeVerifikasi
                    terverifikasi={materi.terverifikasi_industri}
                    className="mt-3"
                />
                <p className="mt-3 text-sm text-muted-foreground">
                    Kompetensi {materi.kompetensi} · dibuat oleh{' '}
                    {materi.pembuat} · diubah{' '}
                    {formatTanggal(materi.diubah_terakhir)}
                </p>
            </header>

            <div className="grid gap-6 lg:grid-cols-[1fr_22rem] lg:items-start">
                <div className="min-w-0">
                    <MateriBaca materi={materi} />
                </div>

                <aside className="space-y-4 lg:sticky lg:top-6">
                    <form
                        onSubmit={kirim}
                        className="rounded-3xl border bg-card p-5 shadow-md"
                    >
                        <h2 className="text-base font-bold text-navy-900">
                            Hasil pemeriksaan Anda
                        </h2>
                        <p className="mt-1 text-xs text-muted-foreground">
                            Hasil dan masukan dikirim lewat email ke guru
                            pembuat materi.
                        </p>

                        <div className="mt-4 grid gap-2" role="radiogroup">
                            <PilihanHasil
                                aktif={form.data.hasil === 'diverifikasi'}
                                onClick={() =>
                                    form.setData('hasil', 'diverifikasi')
                                }
                                icon={BadgeCheck}
                                judul="Verifikasi Materi"
                                ket="Materi sesuai dengan praktik di industri."
                            />
                            <PilihanHasil
                                aktif={belumSesuai}
                                onClick={() =>
                                    form.setData('hasil', 'belum_sesuai')
                                }
                                icon={PenLine}
                                judul="Belum Sesuai"
                                ket="Materi perlu diperbaiki guru."
                            />
                        </div>
                        {form.errors.hasil && (
                            <p className="mt-2 text-sm font-medium text-destructive">
                                {form.errors.hasil}
                            </p>
                        )}

                        <label
                            htmlFor="masukan"
                            className="mt-4 block text-sm font-semibold text-navy-900"
                        >
                            Masukan untuk guru{' '}
                            {belumSesuai ? (
                                <span className="text-gap-tinggi">*</span>
                            ) : (
                                <span className="font-normal text-muted-foreground">
                                    (opsional)
                                </span>
                            )}
                        </label>
                        <Textarea
                            id="masukan"
                            rows={5}
                            className="mt-2"
                            value={form.data.masukan}
                            onChange={(e) =>
                                form.setData('masukan', e.target.value)
                            }
                            placeholder={
                                belumSesuai
                                    ? 'Bagian mana yang belum sesuai dan bagaimana seharusnya di industri?'
                                    : 'Tambahan atau saran (opsional)'
                            }
                            aria-invalid={!!form.errors.masukan}
                        />
                        {form.errors.masukan && (
                            <p className="mt-1 text-sm font-medium text-destructive">
                                {form.errors.masukan}
                            </p>
                        )}

                        <Button
                            type="submit"
                            className="mt-4 w-full"
                            variant={belumSesuai ? 'default' : 'aksen'}
                            disabled={form.processing || form.data.hasil === ''}
                        >
                            {form.processing && (
                                <LoaderCircle className="animate-spin" />
                            )}
                            Kirim hasil pemeriksaan
                        </Button>
                    </form>

                    <section className="rounded-3xl border bg-card p-5 shadow-xs">
                        <h2 className="text-sm font-bold text-navy-900">
                            Riwayat pemeriksaan
                        </h2>
                        {riwayatVerifikasi.length === 0 ? (
                            <p className="mt-2 text-sm text-muted-foreground">
                                Belum pernah diperiksa.
                            </p>
                        ) : (
                            <ol className="mt-3 space-y-3">
                                {riwayatVerifikasi.map((v) => (
                                    <li
                                        key={v.id}
                                        className="rounded-2xl bg-navy-50/70 p-3"
                                    >
                                        <span
                                            className={cn(
                                                'rounded-full px-2 py-0.5 text-[11px] font-bold',
                                                v.hasil === 'diverifikasi'
                                                    ? 'bg-hijau-50 text-hijau-700'
                                                    : 'bg-gap-sedang-soft text-navy-900',
                                            )}
                                        >
                                            {v.hasil_label}
                                        </span>
                                        <p className="mt-2 text-xs text-navy-600">
                                            {v.pemeriksa} · {v.perusahaan} ·{' '}
                                            {formatTanggalWaktu(v.tanggal)}
                                        </p>
                                        {v.masukan && (
                                            <p className="mt-2 flex gap-2 text-sm text-navy-900">
                                                <MessageSquareText className="mt-0.5 size-4 shrink-0 text-navy-400" />
                                                {v.masukan}
                                            </p>
                                        )}
                                    </li>
                                ))}
                            </ol>
                        )}
                    </section>
                </aside>
            </div>
        </>
    );
}

function PilihanHasil({
    aktif,
    onClick,
    icon: Icon,
    judul,
    ket,
}: {
    aktif: boolean;
    onClick: () => void;
    icon: typeof BadgeCheck;
    judul: string;
    ket: string;
}) {
    return (
        <button
            type="button"
            role="radio"
            aria-checked={aktif}
            onClick={onClick}
            className={cn(
                'flex items-start gap-3 rounded-2xl border p-3 text-left transition-colors',
                aktif
                    ? 'border-navy-900 bg-navy-900 text-white'
                    : 'bg-card text-navy-900 hover:bg-navy-50',
            )}
        >
            <Icon
                className={cn(
                    'mt-0.5 size-5 shrink-0',
                    aktif ? 'text-white' : 'text-navy-500',
                )}
            />
            <span>
                <span className="block text-sm font-bold">{judul}</span>
                <span
                    className={cn(
                        'block text-xs',
                        aktif ? 'text-navy-200' : 'text-muted-foreground',
                    )}
                >
                    {ket}
                </span>
            </span>
        </button>
    );
}
