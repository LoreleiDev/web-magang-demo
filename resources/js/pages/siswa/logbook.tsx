import { Head, router, useForm, usePage } from '@inertiajs/react';
import {
    FileText,
    LoaderCircle,
    NotebookPen,
    Paperclip,
    Pencil,
    Plus,
    Sparkles,
    X,
} from 'lucide-react';
import type { FormEvent } from 'react';
import { useRef, useState } from 'react';
import type { HasilAnalisis } from '@/components/ai-mentor/hasil-analisis';
import { HasilAnalisisAi } from '@/components/ai-mentor/hasil-analisis';
import { EmptyState } from '@/components/empty-state';
import { FormField } from '@/components/form-field';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { formatTanggalPanjang } from '@/lib/format';
import { cn } from '@/lib/utils';
import siswa from '@/routes/siswa';
import type { Logbook } from '@/types/kompetensi';

type LogbookSiswa = Logbook & {
    url_bukti: string | null;
    nama_bukti: string | null;
    hasil_analisis_ai: HasilAnalisis | null;
};

type DataForm = {
    tanggal: string;
    aktivitas: string;
    peralatan_software: string;
    sudah_dipahami: string;
    baru_ditemui: string;
    kesulitan: string;
    pengetahuan_sekolah_digunakan: string;
    ingin_dipelajari: string;
    bukti_kegiatan: File | null;
    hapus_bukti: boolean;
};

/** Isian form logbook harian (CLAUDE.md bagian 6.6). */
const isian: {
    kunci: Exclude<
        keyof DataForm,
        'tanggal' | 'bukti_kegiatan' | 'hapus_bukti'
    >;
    label: string;
    wajib?: boolean;
    contoh: string;
}[] = [
    {
        kunci: 'aktivitas',
        label: 'Aktivitas hari ini',
        wajib: true,
        contoh: 'Apa saja yang Anda kerjakan hari ini?',
    },
    {
        kunci: 'peralatan_software',
        label: 'Peralatan/software yang digunakan',
        contoh: 'Contoh: Winbox, tang crimping, VS Code',
    },
    {
        kunci: 'sudah_dipahami',
        label: 'Apa yang sudah saya pahami',
        contoh: 'Hal yang sudah Anda kuasai',
    },
    {
        kunci: 'baru_ditemui',
        label: 'Apa yang baru saya temui',
        contoh: 'Alat, istilah, atau prosedur baru',
    },
    {
        kunci: 'kesulitan',
        label: 'Kesulitan yang saya alami',
        contoh: 'Bagian yang masih membingungkan',
    },
    {
        kunci: 'pengetahuan_sekolah_digunakan',
        label: 'Pengetahuan dari sekolah yang saya gunakan',
        contoh: 'Materi sekolah yang terpakai hari ini',
    },
    {
        kunci: 'ingin_dipelajari',
        label: 'Hal yang ingin saya pelajari',
        contoh: 'Topik yang ingin Anda dalami',
    },
];

function formKosong(tanggal: string): DataForm {
    return {
        tanggal,
        aktivitas: '',
        peralatan_software: '',
        sudah_dipahami: '',
        baru_ditemui: '',
        kesulitan: '',
        pengetahuan_sekolah_digunakan: '',
        ingin_dipelajari: '',
        bukti_kegiatan: null,
        hapus_bukti: false,
    };
}

export default function LogbookSiswaPage({
    logbook,
    hariIni,
}: {
    logbook: LogbookSiswa[];
    hariIni: string;
}) {
    const sudahIsiHariIni = logbook.some((l) => l.tanggal === hariIni);
    const [edit, setEdit] = useState<LogbookSiswa | null>(null);
    const [terbuka, setTerbuka] = useState(!sudahIsiHariIni);
    const formRef = useRef<HTMLDivElement>(null);
    const fileRef = useRef<HTMLInputElement>(null);

    const form = useForm<DataForm>(formKosong(hariIni));
    const { data, setData, errors, processing } = form;

    const bukaBaru = () => {
        setEdit(null);
        form.clearErrors();
        form.setData(formKosong(hariIni));
        setTerbuka(true);
        formRef.current?.scrollIntoView({ behavior: 'smooth' });
    };

    const bukaEdit = (l: LogbookSiswa) => {
        setEdit(l);
        form.clearErrors();
        form.setData({
            tanggal: l.tanggal,
            aktivitas: l.aktivitas,
            peralatan_software: l.peralatan_software ?? '',
            sudah_dipahami: l.sudah_dipahami ?? '',
            baru_ditemui: l.baru_ditemui ?? '',
            kesulitan: l.kesulitan ?? '',
            pengetahuan_sekolah_digunakan:
                l.pengetahuan_sekolah_digunakan ?? '',
            ingin_dipelajari: l.ingin_dipelajari ?? '',
            bukti_kegiatan: null,
            hapus_bukti: false,
        });
        setTerbuka(true);
        formRef.current?.scrollIntoView({ behavior: 'smooth' });
    };

    const simpan = (e: FormEvent) => {
        e.preventDefault();
        const opsi = {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                setTerbuka(false);
                setEdit(null);
                form.reset();
            },
        };

        if (edit) {
            form.transform((d) => ({
                ...d,
                hapus_bukti: d.hapus_bukti ? 1 : 0,
                _method: 'put',
            }));
            form.post(siswa.logbook.update(edit.id).url, opsi);
        } else {
            form.transform((d) => ({ ...d, hapus_bukti: 0 }));
            form.post(siswa.logbook.store().url, opsi);
        }
    };

    return (
        <>
            <Head title="Logbook" />
            <PageHeader
                eyebrow="Logbook harian"
                title="Logbook magang"
                description="Catat kegiatan setiap hari. Satu logbook per tanggal dan bisa diedit."
                actions={
                    !terbuka && (
                        <Button onClick={bukaBaru}>
                            <Plus />
                            Tulis logbook
                        </Button>
                    )
                }
            />

            <div ref={formRef} className="scroll-mt-20">
                {terbuka && (
                    <form
                        onSubmit={simpan}
                        className="mb-8 rounded-3xl border bg-card p-5 shadow-md sm:p-7"
                    >
                        <div className="mb-5 flex items-center justify-between gap-3">
                            <h2 className="text-lg font-extrabold text-navy-900">
                                {edit
                                    ? `Edit logbook ${formatTanggalPanjang(edit.tanggal)}`
                                    : 'Logbook baru'}
                            </h2>
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                onClick={() => {
                                    setTerbuka(false);
                                    setEdit(null);
                                }}
                                aria-label="Tutup form"
                            >
                                <X />
                            </Button>
                        </div>

                        <div className="grid gap-5 md:grid-cols-2">
                            <FormField
                                label="Tanggal"
                                htmlFor="tanggal"
                                error={errors.tanggal}
                                wajib
                            >
                                <Input
                                    id="tanggal"
                                    type="date"
                                    max={hariIni}
                                    value={data.tanggal}
                                    onChange={(e) =>
                                        setData('tanggal', e.target.value)
                                    }
                                    aria-invalid={!!errors.tanggal}
                                />
                            </FormField>
                            <div className="hidden md:block" />
                            {isian.map((f) => (
                                <FormField
                                    key={f.kunci}
                                    label={f.label}
                                    htmlFor={f.kunci}
                                    error={errors[f.kunci]}
                                    wajib={f.wajib}
                                    className={cn(
                                        f.kunci === 'aktivitas' &&
                                            'md:col-span-2',
                                    )}
                                >
                                    <Textarea
                                        id={f.kunci}
                                        rows={f.kunci === 'aktivitas' ? 4 : 3}
                                        value={data[f.kunci]}
                                        onChange={(e) =>
                                            setData(f.kunci, e.target.value)
                                        }
                                        placeholder={f.contoh}
                                        aria-invalid={!!errors[f.kunci]}
                                    />
                                </FormField>
                            ))}
                            <FormField
                                label="Bukti kegiatan (opsional)"
                                error={errors.bukti_kegiatan}
                                hint="Foto atau PDF, maksimal 5 MB."
                                className="md:col-span-2"
                            >
                                {edit?.url_bukti &&
                                    !data.hapus_bukti &&
                                    !data.bukti_kegiatan && (
                                        <div className="flex items-center gap-2 rounded-xl border bg-navy-50/60 px-3 py-2 text-sm">
                                            <Paperclip className="size-4 text-navy-500" />
                                            <a
                                                href={edit.url_bukti}
                                                target="_blank"
                                                rel="noopener"
                                                className="min-w-0 flex-1 truncate font-medium text-navy-800 underline-offset-2 hover:underline"
                                            >
                                                {edit.nama_bukti}
                                            </a>
                                            <Button
                                                type="button"
                                                size="sm"
                                                variant="ghost"
                                                onClick={() =>
                                                    setData('hapus_bukti', true)
                                                }
                                            >
                                                Hapus
                                            </Button>
                                        </div>
                                    )}
                                <div className="flex flex-wrap items-center gap-2">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={() => fileRef.current?.click()}
                                    >
                                        <Paperclip />
                                        {data.bukti_kegiatan
                                            ? 'Ganti file'
                                            : 'Pilih file'}
                                    </Button>
                                    {data.bukti_kegiatan && (
                                        <span className="flex min-w-0 items-center gap-1.5 text-sm text-navy-800">
                                            <FileText className="size-4 shrink-0 text-navy-500" />
                                            <span className="truncate">
                                                {data.bukti_kegiatan.name}
                                            </span>
                                        </span>
                                    )}
                                </div>
                                <input
                                    ref={fileRef}
                                    type="file"
                                    accept=".jpg,.jpeg,.png,.webp,.pdf"
                                    className="sr-only"
                                    onChange={(e) =>
                                        setData(
                                            'bukti_kegiatan',
                                            e.target.files?.[0] ?? null,
                                        )
                                    }
                                />
                            </FormField>
                        </div>

                        <div className="mt-6 flex flex-col-reverse gap-2 border-t pt-5 sm:flex-row sm:justify-end">
                            <Button
                                type="button"
                                variant="ghost"
                                onClick={() => {
                                    setTerbuka(false);
                                    setEdit(null);
                                }}
                            >
                                Batal
                            </Button>
                            <Button type="submit" disabled={processing}>
                                {processing && (
                                    <LoaderCircle className="animate-spin" />
                                )}
                                {edit ? 'Simpan perubahan' : 'Simpan logbook'}
                            </Button>
                        </div>
                    </form>
                )}
            </div>

            <h2 className="mb-3 text-base font-bold text-navy-900">
                Riwayat logbook ({logbook.length})
            </h2>
            {logbook.length === 0 ? (
                <EmptyState
                    icon={NotebookPen}
                    title="Belum ada logbook"
                    description="Mulai dengan menuliskan kegiatan Anda hari ini."
                />
            ) : (
                <ul className="space-y-3">
                    {logbook.map((l) => (
                        <li
                            key={l.id}
                            className="rounded-3xl border bg-card p-5 shadow-xs"
                        >
                            <div className="flex items-start justify-between gap-3">
                                <div className="min-w-0">
                                    <p className="text-xs font-semibold text-hijau-600">
                                        {formatTanggalPanjang(l.tanggal)}
                                        {l.tanggal === hariIni && ' · hari ini'}
                                    </p>
                                    <p className="mt-1.5 text-sm whitespace-pre-line text-navy-900">
                                        {l.aktivitas}
                                    </p>
                                </div>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => bukaEdit(l)}
                                >
                                    <Pencil />
                                    Edit
                                </Button>
                            </div>
                            {(l.kesulitan || l.ingin_dipelajari) && (
                                <dl className="mt-3 grid gap-2 sm:grid-cols-2">
                                    {l.kesulitan && (
                                        <div className="rounded-2xl bg-navy-50/60 p-3">
                                            <dt className="text-xs font-semibold text-navy-500">
                                                Kesulitan
                                            </dt>
                                            <dd className="mt-0.5 text-sm text-navy-900">
                                                {l.kesulitan}
                                            </dd>
                                        </div>
                                    )}
                                    {l.ingin_dipelajari && (
                                        <div className="rounded-2xl bg-navy-50/60 p-3">
                                            <dt className="text-xs font-semibold text-navy-500">
                                                Ingin dipelajari
                                            </dt>
                                            <dd className="mt-0.5 text-sm text-navy-900">
                                                {l.ingin_dipelajari}
                                            </dd>
                                        </div>
                                    )}
                                </dl>
                            )}
                            {l.url_bukti && (
                                <a
                                    href={l.url_bukti}
                                    target="_blank"
                                    rel="noopener"
                                    className="mt-3 inline-flex items-center gap-1.5 text-xs font-semibold text-navy-600 hover:text-navy-900"
                                >
                                    <Paperclip className="size-3.5" />
                                    Lihat bukti kegiatan
                                </a>
                            )}
                            <AnalisisLogbook logbook={l} />
                        </li>
                    ))}
                </ul>
            )}
        </>
    );
}

/**
 * Tombol "Analisis dengan AI" (bagian 6.6) + hasilnya. Pesan gagal dari backend
 * ditampilkan di bawah tombol.
 */
function AnalisisLogbook({ logbook }: { logbook: LogbookSiswa }) {
    const { errors } = usePage().props;
    const [proses, setProses] = useState(false);
    const [aktif, setAktif] = useState(false);
    const pesanError = aktif
        ? (errors as Record<string, string>).analisis
        : undefined;

    const analisis = () =>
        router.post(
            siswa.logbook.analisis(logbook.id).url,
            {},
            {
                preserveScroll: true,
                onStart: () => {
                    setProses(true);
                    setAktif(true);
                },
                onFinish: () => setProses(false),
            },
        );

    return (
        <div className="mt-3">
            {logbook.hasil_analisis_ai && (
                <HasilAnalisisAi hasil={logbook.hasil_analisis_ai} />
            )}
            <Button
                type="button"
                variant={logbook.hasil_analisis_ai ? 'outline' : 'aksen'}
                size="sm"
                className="mt-3"
                disabled={proses}
                onClick={analisis}
            >
                {proses ? (
                    <LoaderCircle className="animate-spin" />
                ) : (
                    <Sparkles />
                )}
                {proses
                    ? 'AI sedang menganalisis…'
                    : logbook.hasil_analisis_ai
                      ? 'Analisis ulang dengan AI'
                      : 'Analisis dengan AI'}
            </Button>
            {pesanError && (
                <p className="mt-2 text-sm font-medium text-destructive">
                    {pesanError}
                </p>
            )}
        </div>
    );
}
