import { Head, Link, router, useForm } from '@inertiajs/react';
import {
    ArrowLeft,
    Download,
    FileText,
    LoaderCircle,
    Plus,
    Trash2,
    X,
} from 'lucide-react';
import type { FormEvent } from 'react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { FormField, FormSection } from '@/components/form-field';
import { PageHeader } from '@/components/page-header';
import { PilihFile } from '@/components/pilih-file';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { formatTanggalWaktu } from '@/lib/format';
import perusahaanRoutes from '@/routes/superadmin/perusahaan';

type DataPerusahaan = {
    id: number;
    nama: string;
    alamat: string | null;
    bidang_usaha: string | null;
    daftar_unit_kerja: string[];
};

type Dokumen = {
    id: number;
    nama_file: string;
    diunggah_oleh: string;
    diunggah_pada: string | null;
};

type AturanDokumen = { ekstensi: string[]; maks_kb: number };

type Props = {
    perusahaan: DataPerusahaan | null;
    dokumen: Dokumen[];
    aturanDokumen: AturanDokumen;
};

/**
 * Ambil pesan error per indeks untuk isian array, contoh "dokumen.2".
 */
function errorArray(errors: Record<string, string>, kunci: string): string[] {
    return Object.entries(errors).reduce<string[]>((hasil, [k, v]) => {
        const cocok = k.match(new RegExp(`^${kunci}\\.(\\d+)$`));

        if (cocok) {
            hasil[Number(cocok[1])] = v;
        }

        return hasil;
    }, []);
}

export default function PerusahaanForm({
    perusahaan,
    dokumen,
    aturanDokumen,
}: Props) {
    const edit = perusahaan !== null;

    const form = useForm<{
        nama: string;
        alamat: string;
        bidang_usaha: string;
        daftar_unit_kerja: string[];
        dokumen: File[];
    }>({
        nama: perusahaan?.nama ?? '',
        alamat: perusahaan?.alamat ?? '',
        bidang_usaha: perusahaan?.bidang_usaha ?? '',
        daftar_unit_kerja: perusahaan?.daftar_unit_kerja ?? [''],
        dokumen: [],
    });
    const { data, setData, errors, processing } = form;
    const semuaError = errors as Record<string, string>;
    const errorUnit = errorArray(semuaError, 'daftar_unit_kerja');

    const ubahUnit = (i: number, nilai: string) =>
        setData(
            'daftar_unit_kerja',
            data.daftar_unit_kerja.map((u, j) => (j === i ? nilai : u)),
        );

    const simpan = (e: FormEvent) => {
        e.preventDefault();

        if (edit) {
            form.transform(({ dokumen: _dokumen, ...rest }) => rest);
            form.put(perusahaanRoutes.update(perusahaan.id).url, {
                preserveScroll: true,
            });
        } else {
            form.post(perusahaanRoutes.store().url, {
                forceFormData: true,
                preserveScroll: true,
            });
        }
    };

    return (
        <>
            <Head
                title={edit ? `Edit ${perusahaan.nama}` : 'Tambah perusahaan'}
            />

            <Link
                href={perusahaanRoutes.index().url}
                className="mb-4 inline-flex items-center gap-1.5 text-sm font-semibold text-navy-600 hover:text-navy-900"
            >
                <ArrowLeft className="size-4" /> Daftar perusahaan
            </Link>

            <PageHeader
                title={edit ? perusahaan.nama : 'Tambah perusahaan'}
                description="Data perusahaan menjadi sumber nama industri dan pilihan unit kerja di halaman siswa."
            />

            <form
                onSubmit={simpan}
                className="rounded-3xl border bg-card p-5 shadow-xs sm:p-8"
            >
                <FormSection title="Profil perusahaan">
                    <FormField
                        label="Nama perusahaan"
                        htmlFor="nama"
                        error={errors.nama}
                        wajib
                    >
                        <Input
                            id="nama"
                            value={data.nama}
                            onChange={(e) => setData('nama', e.target.value)}
                            aria-invalid={!!errors.nama}
                        />
                    </FormField>
                    <FormField
                        label="Bidang usaha"
                        htmlFor="bidang_usaha"
                        error={errors.bidang_usaha}
                    >
                        <Input
                            id="bidang_usaha"
                            value={data.bidang_usaha}
                            onChange={(e) =>
                                setData('bidang_usaha', e.target.value)
                            }
                            placeholder="Contoh: Penyedia layanan internet"
                        />
                    </FormField>
                    <FormField
                        label="Alamat"
                        htmlFor="alamat"
                        error={errors.alamat}
                    >
                        <Textarea
                            id="alamat"
                            rows={3}
                            value={data.alamat}
                            onChange={(e) => setData('alamat', e.target.value)}
                        />
                    </FormField>
                </FormSection>

                <FormSection
                    title="Unit kerja"
                    description="Bagian/divisi tempat siswa bisa ditempatkan. Siswa memilih salah satunya saat mulai pendampingan."
                >
                    <div className="space-y-2">
                        {data.daftar_unit_kerja.map((unit, i) => (
                            <div key={i}>
                                <div className="flex gap-2">
                                    <Input
                                        value={unit}
                                        onChange={(e) =>
                                            ubahUnit(i, e.target.value)
                                        }
                                        placeholder={`Unit kerja ${i + 1}`}
                                        aria-label={`Unit kerja ${i + 1}`}
                                        aria-invalid={!!errorUnit[i]}
                                    />
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        disabled={
                                            data.daftar_unit_kerja.length === 1
                                        }
                                        onClick={() =>
                                            setData(
                                                'daftar_unit_kerja',
                                                data.daftar_unit_kerja.filter(
                                                    (_, j) => j !== i,
                                                ),
                                            )
                                        }
                                        aria-label={`Hapus unit kerja ${i + 1}`}
                                    >
                                        <X />
                                    </Button>
                                </div>
                                {errorUnit[i] && (
                                    <p className="mt-1 text-sm font-medium text-destructive">
                                        {errorUnit[i]}
                                    </p>
                                )}
                            </div>
                        ))}
                    </div>
                    {errors.daftar_unit_kerja && (
                        <p className="text-sm font-medium text-destructive">
                            {errors.daftar_unit_kerja}
                        </p>
                    )}
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={() =>
                            setData('daftar_unit_kerja', [
                                ...data.daftar_unit_kerja,
                                '',
                            ])
                        }
                    >
                        <Plus />
                        Tambah unit kerja
                    </Button>
                </FormSection>

                {!edit && (
                    <FormSection
                        title="Dokumen industri"
                        description="SOP, panduan kerja, atau dokumen lain yang menjadi rujukan AI Mentor untuk siswa di perusahaan ini. Bisa ditambah nanti."
                    >
                        <PilihFile
                            files={data.dokumen}
                            onChange={(files) => setData('dokumen', files)}
                            ekstensi={aturanDokumen.ekstensi}
                            maksKb={aturanDokumen.maks_kb}
                            errors={errorArray(semuaError, 'dokumen')}
                        />
                    </FormSection>
                )}

                <div className="flex flex-col-reverse gap-2 border-t pt-6 sm:flex-row sm:justify-end">
                    <Button asChild variant="ghost">
                        <Link href={perusahaanRoutes.index().url}>Batal</Link>
                    </Button>
                    <Button type="submit" disabled={processing}>
                        {processing && (
                            <LoaderCircle className="animate-spin" />
                        )}
                        {edit ? 'Simpan perubahan' : 'Simpan perusahaan'}
                    </Button>
                </div>
            </form>

            {edit && (
                <DokumenIndustri
                    perusahaanId={perusahaan.id}
                    dokumen={dokumen}
                    aturan={aturanDokumen}
                />
            )}
        </>
    );
}

function DokumenIndustri({
    perusahaanId,
    dokumen,
    aturan,
}: {
    perusahaanId: number;
    dokumen: Dokumen[];
    aturan: AturanDokumen;
}) {
    const form = useForm<{ dokumen: File[] }>({ dokumen: [] });
    const semuaError = form.errors as Record<string, string>;

    const unggah = (e: FormEvent) => {
        e.preventDefault();
        form.post(perusahaanRoutes.dokumen.store(perusahaanId).url, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    };

    return (
        <section className="mt-6 rounded-3xl border bg-card p-5 shadow-xs sm:p-8">
            <div className="grid gap-5 lg:grid-cols-[16rem_1fr] lg:gap-10">
                <div>
                    <h2 className="text-base font-bold text-navy-900">
                        Dokumen industri
                    </h2>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Knowledge base AI Mentor khusus untuk siswa yang magang
                        di perusahaan ini.
                    </p>
                </div>

                <div className="min-w-0 space-y-5">
                    {dokumen.length === 0 ? (
                        <p className="rounded-2xl bg-navy-50/70 px-4 py-3 text-sm text-muted-foreground">
                            Belum ada dokumen.
                        </p>
                    ) : (
                        <ul className="divide-y rounded-2xl border">
                            {dokumen.map((d) => (
                                <li
                                    key={d.id}
                                    className="flex items-center gap-3 px-4 py-3"
                                >
                                    <FileText className="size-5 shrink-0 text-navy-500" />
                                    <div className="min-w-0 flex-1">
                                        <div className="truncate text-sm font-semibold text-navy-900">
                                            {d.nama_file}
                                        </div>
                                        <div className="text-xs text-muted-foreground">
                                            {d.diunggah_oleh} ·{' '}
                                            {formatTanggalWaktu(
                                                d.diunggah_pada,
                                            )}
                                        </div>
                                    </div>
                                    <Button
                                        asChild
                                        variant="ghost"
                                        size="icon-sm"
                                    >
                                        <a
                                            href={
                                                perusahaanRoutes.dokumen.show({
                                                    perusahaan: perusahaanId,
                                                    dokumen: d.id,
                                                }).url
                                            }
                                            aria-label={`Unduh ${d.nama_file}`}
                                        >
                                            <Download />
                                        </a>
                                    </Button>
                                    <ConfirmDialog
                                        trigger={
                                            <Button
                                                variant="ghost"
                                                size="icon-sm"
                                                aria-label={`Hapus ${d.nama_file}`}
                                            >
                                                <Trash2 />
                                            </Button>
                                        }
                                        title="Hapus dokumen?"
                                        description={`${d.nama_file} tidak akan dipakai lagi sebagai rujukan AI Mentor.`}
                                        confirmLabel="Hapus"
                                        destructive
                                        onConfirm={() =>
                                            router.delete(
                                                perusahaanRoutes.dokumen.destroy(
                                                    {
                                                        perusahaan:
                                                            perusahaanId,
                                                        dokumen: d.id,
                                                    },
                                                ).url,
                                                { preserveScroll: true },
                                            )
                                        }
                                    />
                                </li>
                            ))}
                        </ul>
                    )}

                    <form onSubmit={unggah} className="space-y-3">
                        <PilihFile
                            files={form.data.dokumen}
                            onChange={(files) => form.setData('dokumen', files)}
                            ekstensi={aturan.ekstensi}
                            maksKb={aturan.maks_kb}
                            errors={errorArray(semuaError, 'dokumen')}
                        />
                        {form.errors.dokumen && (
                            <p className="text-sm font-medium text-destructive">
                                {form.errors.dokumen}
                            </p>
                        )}
                        {form.data.dokumen.length > 0 && (
                            <Button type="submit" disabled={form.processing}>
                                {form.processing && (
                                    <LoaderCircle className="animate-spin" />
                                )}
                                Unggah {form.data.dokumen.length} dokumen
                            </Button>
                        )}
                    </form>
                </div>
            </div>
        </section>
    );
}
