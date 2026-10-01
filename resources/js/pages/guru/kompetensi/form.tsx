import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, LoaderCircle } from 'lucide-react';
import type { FormEvent } from 'react';
import { FormField, FormSection } from '@/components/form-field';
import { namaLevel } from '@/components/kompetensi-indikator';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';
import guru from '@/routes/guru';

type Props = {
    kompetensi: {
        id: number;
        nama_kompetensi_sekolah: string;
        aktivitas_kompetensi_industri: string;
        target_level: number;
    } | null;
    program: string | null;
};

export default function KompetensiForm({ kompetensi, program }: Props) {
    const edit = kompetensi !== null;
    const form = useForm({
        nama_kompetensi_sekolah: kompetensi?.nama_kompetensi_sekolah ?? '',
        aktivitas_kompetensi_industri:
            kompetensi?.aktivitas_kompetensi_industri ?? '',
        target_level: kompetensi?.target_level ?? 3,
    });
    const { data, setData, errors, processing } = form;

    const simpan = (e: FormEvent) => {
        e.preventDefault();

        if (edit) {
            form.put(guru.kompetensi.update(kompetensi.id).url);
        } else {
            form.post(guru.kompetensi.store().url);
        }
    };

    return (
        <>
            <Head title={edit ? 'Edit kompetensi' : 'Tambah kompetensi'} />
            <Link
                href={guru.kompetensi.index().url}
                className="mb-4 inline-flex items-center gap-1.5 text-sm font-semibold text-navy-600 hover:text-navy-900"
            >
                <ArrowLeft className="size-4" /> Daftar kompetensi
            </Link>
            <PageHeader
                eyebrow={program ?? undefined}
                title={edit ? 'Edit kompetensi' : 'Tambah kompetensi'}
            />

            <form
                onSubmit={simpan}
                className="rounded-3xl border bg-card p-5 shadow-xs sm:p-8"
            >
                <FormSection
                    title="Pasangan kompetensi"
                    description="Tuliskan kompetensi dari kurikulum sekolah dan aktivitas nyata di industri yang berkaitan."
                >
                    <FormField
                        label="Kompetensi sekolah"
                        htmlFor="nama"
                        error={errors.nama_kompetensi_sekolah}
                        wajib
                    >
                        <Input
                            id="nama"
                            value={data.nama_kompetensi_sekolah}
                            onChange={(e) =>
                                setData(
                                    'nama_kompetensi_sekolah',
                                    e.target.value,
                                )
                            }
                            placeholder="Contoh: Pengalamatan IP dan subnetting"
                            aria-invalid={!!errors.nama_kompetensi_sekolah}
                        />
                    </FormField>
                    <FormField
                        label="Aktivitas / kompetensi industri"
                        htmlFor="aktivitas"
                        error={errors.aktivitas_kompetensi_industri}
                        wajib
                    >
                        <Textarea
                            id="aktivitas"
                            rows={3}
                            value={data.aktivitas_kompetensi_industri}
                            onChange={(e) =>
                                setData(
                                    'aktivitas_kompetensi_industri',
                                    e.target.value,
                                )
                            }
                            placeholder="Contoh: Merencanakan pembagian alamat IP untuk pelanggan baru"
                            aria-invalid={
                                !!errors.aktivitas_kompetensi_industri
                            }
                        />
                    </FormField>
                </FormSection>

                <FormSection
                    title="Target level"
                    description="Level yang harus dicapai siswa. Gap = target − level siswa."
                >
                    <div
                        className="grid gap-2 sm:grid-cols-2"
                        role="radiogroup"
                        aria-label="Target level"
                    >
                        {[1, 2, 3, 4].map((n) => (
                            <button
                                key={n}
                                type="button"
                                role="radio"
                                aria-checked={data.target_level === n}
                                onClick={() => setData('target_level', n)}
                                className={cn(
                                    'flex items-center gap-3 rounded-2xl border p-3 text-left transition-colors',
                                    data.target_level === n
                                        ? 'border-navy-900 bg-navy-900 text-white'
                                        : 'bg-card text-navy-900 hover:bg-navy-50',
                                )}
                            >
                                <span
                                    className={cn(
                                        'grid size-9 shrink-0 place-items-center rounded-xl text-sm font-extrabold',
                                        data.target_level === n
                                            ? 'bg-white/10'
                                            : 'bg-navy-50',
                                    )}
                                >
                                    {n}
                                </span>
                                <span className="text-sm font-semibold">
                                    {namaLevel(n)}
                                </span>
                            </button>
                        ))}
                    </div>
                    {errors.target_level && (
                        <p className="text-sm font-medium text-destructive">
                            {errors.target_level}
                        </p>
                    )}
                </FormSection>

                <div className="flex flex-col-reverse gap-2 border-t pt-6 sm:flex-row sm:justify-end">
                    <Button asChild variant="ghost">
                        <Link href={guru.kompetensi.index().url}>Batal</Link>
                    </Button>
                    <Button type="submit" disabled={processing}>
                        {processing && (
                            <LoaderCircle className="animate-spin" />
                        )}
                        {edit ? 'Simpan perubahan' : 'Simpan kompetensi'}
                    </Button>
                </div>
            </form>
        </>
    );
}
