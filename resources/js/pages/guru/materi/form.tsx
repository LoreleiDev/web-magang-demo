import { Head, Link, useForm } from '@inertiajs/react';
import {
    ArrowLeft,
    BadgeCheck,
    ChevronLeft,
    ChevronRight,
    LoaderCircle,
    MessageSquareText,
    Plus,
} from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import type { MediaForm } from '@/components/editor-media';
import { EditorMedia } from '@/components/editor-media';
import type { SoalForm } from '@/components/editor-soal';
import { EditorSoal } from '@/components/editor-soal';
import { FormField } from '@/components/form-field';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { formatTanggalWaktu } from '@/lib/format';
import { cn } from '@/lib/utils';
import guru from '@/routes/guru';
import type { RiwayatVerifikasi } from '@/types/materi';

type LangkahForm = { konten_teks: string; media: MediaForm[] };

type DataForm = {
    kompetensi_id: string;
    judul: string;
    langkah: LangkahForm[];
    soal: SoalForm[];
};

type Props = {
    materi: {
        id: number;
        kompetensi_id: number;
        judul: string;
        terverifikasi_industri: boolean;
        langkah: LangkahForm[];
        soal: SoalForm[];
    } | null;
    program: string | null;
    kompetensi: { id: number; nama: string }[];
    judulLangkah: string[];
    riwayatVerifikasi: RiwayatVerifikasi[];
};

const soalKosong = (): SoalForm => ({
    pertanyaan: '',
    pilihan: ['', '', '', ''],
    jawaban_benar: null,
    pembahasan: '',
});

const INDEKS_KUIS = 5;

export default function MateriForm({
    materi,
    program,
    kompetensi,
    judulLangkah,
    riwayatVerifikasi,
}: Props) {
    const edit = materi !== null;
    const [langkahAktif, setLangkahAktif] = useState(0);

    const form = useForm<DataForm>({
        kompetensi_id: materi ? String(materi.kompetensi_id) : '',
        judul: materi?.judul ?? '',
        langkah:
            materi?.langkah ??
            judulLangkah.map(() => ({ konten_teks: '', media: [] })),
        soal: materi?.soal.length ? materi.soal : [soalKosong()],
    });
    const { data, setData, processing } = form;
    const errors = form.errors as Record<string, string>;

    const errorLangkah = (i: number) =>
        Object.keys(errors).some((k) => k.startsWith(`langkah.${i}.`));
    const errorSoal = Object.keys(errors).some((k) => k.startsWith('soal'));

    const ubahLangkah = (i: number, langkah: LangkahForm) =>
        setData(
            'langkah',
            data.langkah.map((l, j) => (j === i ? langkah : l)),
        );

    const simpan = (e: FormEvent) => {
        e.preventDefault();

        const opsi = {
            preserveScroll: true,
            onError: (err: Record<string, string>) => {
                const pertama = Object.keys(err)
                    .map((k) => k.match(/^langkah\.(\d+)\./)?.[1])
                    .find((x) => x !== undefined);

                if (pertama !== undefined) {
                    setLangkahAktif(Number(pertama));
                }
            },
        };

        if (edit) {
            form.put(guru.materi.update(materi.id).url, opsi);
        } else {
            form.post(guru.materi.store().url, opsi);
        }
    };

    const langkah = data.langkah[langkahAktif];

    return (
        <>
            <Head title={edit ? `Edit ${materi.judul}` : 'Tambah materi'} />
            <Link
                href={guru.materi.index().url}
                className="mb-4 inline-flex items-center gap-1.5 text-sm font-semibold text-navy-600 hover:text-navy-900"
            >
                <ArrowLeft className="size-4" /> Daftar materi
            </Link>
            <PageHeader
                eyebrow={program ?? undefined}
                title={edit ? 'Edit materi' : 'Tambah materi'}
                description="Materi microlearning 8 langkah. Semua media dimasukkan sebagai link."
            />

            <form
                onSubmit={simpan}
                className="grid gap-6 xl:grid-cols-[1fr_20rem] xl:items-start"
            >
                <div className="min-w-0 space-y-6">
                    <section className="grid gap-5 rounded-3xl border bg-card p-5 shadow-xs sm:grid-cols-2 sm:p-6">
                        <FormField
                            label="Judul materi"
                            htmlFor="judul"
                            error={errors.judul}
                            wajib
                            className="sm:col-span-2"
                        >
                            <Input
                                id="judul"
                                value={data.judul}
                                onChange={(e) =>
                                    setData('judul', e.target.value)
                                }
                                aria-invalid={!!errors.judul}
                            />
                        </FormField>
                        <FormField
                            label="Kompetensi"
                            error={errors.kompetensi_id}
                            wajib
                            className="sm:col-span-2"
                            hint={
                                kompetensi.length === 0
                                    ? 'Belum ada kompetensi. Buat kompetensi dulu di menu Kompetensi.'
                                    : undefined
                            }
                        >
                            <Select
                                value={data.kompetensi_id}
                                onValueChange={(v) =>
                                    setData('kompetensi_id', v)
                                }
                            >
                                <SelectTrigger
                                    className="w-full"
                                    aria-invalid={!!errors.kompetensi_id}
                                >
                                    <SelectValue placeholder="Pilih kompetensi" />
                                </SelectTrigger>
                                <SelectContent>
                                    {kompetensi.map((k) => (
                                        <SelectItem
                                            key={k.id}
                                            value={String(k.id)}
                                        >
                                            {k.nama}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </FormField>
                    </section>

                    <section className="rounded-3xl border bg-card p-2 shadow-xs">
                        <nav
                            aria-label="Langkah materi"
                            className="flex gap-1 overflow-x-auto p-2"
                        >
                            {judulLangkah.map((judul, i) => {
                                const terisi =
                                    data.langkah[i].konten_teks.trim() !== '' ||
                                    data.langkah[i].media.length > 0;

                                return (
                                    <button
                                        key={judul}
                                        type="button"
                                        onClick={() => setLangkahAktif(i)}
                                        aria-current={
                                            langkahAktif === i
                                                ? 'step'
                                                : undefined
                                        }
                                        className={cn(
                                            'flex shrink-0 items-center gap-2 rounded-xl px-3 py-2 text-left text-xs font-semibold transition-colors',
                                            langkahAktif === i
                                                ? 'bg-navy-900 text-white'
                                                : 'text-navy-700 hover:bg-navy-50',
                                        )}
                                    >
                                        <span
                                            className={cn(
                                                'grid size-6 place-items-center rounded-lg text-[11px] font-extrabold',
                                                errorLangkah(i)
                                                    ? 'bg-gap-tinggi text-white'
                                                    : terisi
                                                      ? 'bg-hijau-500 text-white'
                                                      : langkahAktif === i
                                                        ? 'bg-white/15'
                                                        : 'bg-navy-100',
                                            )}
                                        >
                                            {i + 1}
                                        </span>
                                        {judul}
                                    </button>
                                );
                            })}
                        </nav>

                        <div className="space-y-4 rounded-2xl bg-navy-50/60 p-4 sm:p-5">
                            <div className="flex items-center justify-between gap-3">
                                <h2 className="text-base font-bold text-navy-900">
                                    Langkah {langkahAktif + 1}:{' '}
                                    {judulLangkah[langkahAktif]}
                                </h2>
                                <div className="flex gap-1">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="icon-sm"
                                        disabled={langkahAktif === 0}
                                        onClick={() =>
                                            setLangkahAktif((i) => i - 1)
                                        }
                                        aria-label="Langkah sebelumnya"
                                    >
                                        <ChevronLeft />
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="icon-sm"
                                        disabled={
                                            langkahAktif ===
                                            judulLangkah.length - 1
                                        }
                                        onClick={() =>
                                            setLangkahAktif((i) => i + 1)
                                        }
                                        aria-label="Langkah berikutnya"
                                    >
                                        <ChevronRight />
                                    </Button>
                                </div>
                            </div>

                            <Textarea
                                rows={8}
                                value={langkah.konten_teks}
                                onChange={(e) =>
                                    ubahLangkah(langkahAktif, {
                                        ...langkah,
                                        konten_teks: e.target.value,
                                    })
                                }
                                placeholder={`Isi langkah ${judulLangkah[langkahAktif]}`}
                                aria-label={`Isi langkah ${judulLangkah[langkahAktif]}`}
                                className="bg-card"
                            />
                            {errors[`langkah.${langkahAktif}.konten_teks`] && (
                                <p className="text-sm font-medium text-destructive">
                                    {
                                        errors[
                                            `langkah.${langkahAktif}.konten_teks`
                                        ]
                                    }
                                </p>
                            )}

                            {langkahAktif === INDEKS_KUIS && (
                                <p className="rounded-xl bg-card px-3 py-2 text-sm text-navy-700">
                                    Soal kuis diisi di bagian{' '}
                                    <strong>Kuis</strong> di bawah. Teks di atas
                                    tampil sebagai pengantar kuis.
                                </p>
                            )}

                            <div className="space-y-3">
                                {langkah.media.map((m, j) => (
                                    <EditorMedia
                                        key={j}
                                        media={m}
                                        onChange={(baru) =>
                                            ubahLangkah(langkahAktif, {
                                                ...langkah,
                                                media: langkah.media.map(
                                                    (x, k) =>
                                                        k === j ? baru : x,
                                                ),
                                            })
                                        }
                                        onHapus={() =>
                                            ubahLangkah(langkahAktif, {
                                                ...langkah,
                                                media: langkah.media.filter(
                                                    (_, k) => k !== j,
                                                ),
                                            })
                                        }
                                        errors={{
                                            url: errors[
                                                `langkah.${langkahAktif}.media.${j}.url`
                                            ],
                                        }}
                                    />
                                ))}
                                {langkah.media.length < 5 && (
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        className="bg-card"
                                        onClick={() =>
                                            ubahLangkah(langkahAktif, {
                                                ...langkah,
                                                media: [
                                                    ...langkah.media,
                                                    {
                                                        jenis:
                                                            langkahAktif === 2
                                                                ? 'youtube'
                                                                : 'dokumen',
                                                        url: '',
                                                        keterangan: '',
                                                    },
                                                ],
                                            })
                                        }
                                    >
                                        <Plus />
                                        Tambah media (link)
                                    </Button>
                                )}
                            </div>
                        </div>
                    </section>

                    <section className="space-y-3">
                        <div>
                            <h2 className="text-lg font-bold text-navy-900">
                                Kuis
                            </h2>
                            <p className="text-sm text-muted-foreground">
                                Dikerjakan siswa di langkah 6. Nilai lulus 75.
                            </p>
                            {(errors.soal ?? (errorSoal && !errors.soal)) && (
                                <p className="mt-1 text-sm font-medium text-destructive">
                                    {errors.soal ??
                                        'Periksa kembali soal yang ditandai.'}
                                </p>
                            )}
                        </div>
                        {data.soal.map((s, i) => (
                            <EditorSoal
                                key={i}
                                nomor={i + 1}
                                soal={s}
                                onChange={(baru) =>
                                    setData(
                                        'soal',
                                        data.soal.map((x, j) =>
                                            j === i ? baru : x,
                                        ),
                                    )
                                }
                                onHapus={() =>
                                    setData(
                                        'soal',
                                        data.soal.filter((_, j) => j !== i),
                                    )
                                }
                                bisaDihapus={data.soal.length > 1}
                                errors={(kunci) => errors[`soal.${i}.${kunci}`]}
                            />
                        ))}
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() =>
                                setData('soal', [...data.soal, soalKosong()])
                            }
                        >
                            <Plus />
                            Tambah soal
                        </Button>
                    </section>
                </div>

                <aside className="space-y-4 xl:sticky xl:top-6">
                    <div className="rounded-3xl border bg-card p-5 shadow-xs">
                        {edit && materi.terverifikasi_industri && (
                            <p className="mb-3 flex items-center gap-1.5 text-sm font-semibold text-hijau-700">
                                <BadgeCheck className="size-4" />
                                Diverifikasi oleh industri
                            </p>
                        )}
                        <Button
                            type="submit"
                            size="lg"
                            className="w-full"
                            disabled={processing}
                        >
                            {processing && (
                                <LoaderCircle className="animate-spin" />
                            )}
                            {edit ? 'Simpan perubahan' : 'Simpan materi'}
                        </Button>
                        {Object.keys(errors).length > 0 && (
                            <p className="mt-3 text-sm font-medium text-destructive">
                                Ada isian yang perlu diperbaiki. Langkah yang
                                bermasalah ditandai merah.
                            </p>
                        )}
                    </div>

                    {edit && (
                        <div className="rounded-3xl border bg-card p-5 shadow-xs">
                            <h2 className="text-sm font-bold text-navy-900">
                                Masukan dari industri
                            </h2>
                            {riwayatVerifikasi.length === 0 ? (
                                <p className="mt-2 text-sm text-muted-foreground">
                                    Belum ada pemeriksaan dari industri.
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
                        </div>
                    )}
                </aside>
            </form>
        </>
    );
}
