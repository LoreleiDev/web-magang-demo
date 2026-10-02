import { Head, router, useForm } from '@inertiajs/react';
import { Download, FileText, LoaderCircle, Trash2 } from 'lucide-react';
import type { FormEvent } from 'react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { EmptyState } from '@/components/empty-state';
import { PageHeader } from '@/components/page-header';
import { PilihFile } from '@/components/pilih-file';
import { StatusDokumenAi } from '@/components/status-dokumen-ai';
import type { StatusAi } from '@/components/status-dokumen-ai';
import { TanpaProgram } from '@/components/tanpa-program';
import { Button } from '@/components/ui/button';
import { formatTanggalWaktu } from '@/lib/format';
import guru from '@/routes/guru';

type Dokumen = {
    id: number;
    nama_file: string;
    diunggah_oleh: string;
    diunggah_pada: string | null;
    status_ai: StatusAi;
    pesan_error_ai: string | null;
};

export default function DokumenSekolah({
    program,
    dokumen,
    aturanDokumen,
}: {
    program: { kode: string; nama: string } | null;
    dokumen: Dokumen[];
    aturanDokumen: { ekstensi: string[]; maks_kb: number };
}) {
    const form = useForm<{ dokumen: File[] }>({ dokumen: [] });
    const semuaError = form.errors as Record<string, string>;
    const errorFile = Object.entries(semuaError).reduce<string[]>(
        (hasil, [k, v]) => {
            const cocok = k.match(/^dokumen\.(\d+)$/);

            if (cocok) {
                hasil[Number(cocok[1])] = v;
            }

            return hasil;
        },
        [],
    );

    const unggah = (e: FormEvent) => {
        e.preventDefault();
        form.post(guru.dokumen.store().url, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    };

    return (
        <>
            <Head title="Dokumen Sekolah" />
            <PageHeader
                eyebrow={program?.nama ?? 'Knowledge base'}
                title="Dokumen sekolah"
                description="Modul, jobsheet, atau rangkuman materi yang menjadi rujukan AI Mentor untuk siswa program keahlian Anda."
            />

            {program === null ? (
                <TanpaProgram />
            ) : (
                <div className="grid gap-6 lg:grid-cols-[1fr_22rem] lg:items-start">
                    {dokumen.length === 0 ? (
                        <EmptyState
                            icon={FileText}
                            title="Belum ada dokumen sekolah"
                            description="Dokumen yang diunggah akan dipakai AI Mentor hanya untuk siswa program keahlian ini."
                        />
                    ) : (
                        <ul className="overflow-hidden rounded-3xl border bg-card shadow-xs">
                            {dokumen.map((d) => (
                                <li
                                    key={d.id}
                                    className="flex items-center gap-3 border-b px-5 py-4 last:border-none"
                                >
                                    <span className="grid size-10 shrink-0 place-items-center rounded-xl bg-navy-50 text-navy-600">
                                        <FileText className="size-5" />
                                    </span>
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
                                        <div className="mt-1.5">
                                            <StatusDokumenAi
                                                status={d.status_ai}
                                                pesanError={d.pesan_error_ai}
                                            />
                                        </div>
                                    </div>
                                    <Button
                                        asChild
                                        variant="ghost"
                                        size="icon-sm"
                                    >
                                        <a
                                            href={guru.dokumen.show(d.id).url}
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
                                                guru.dokumen.destroy(d.id).url,
                                                { preserveScroll: true },
                                            )
                                        }
                                    />
                                </li>
                            ))}
                        </ul>
                    )}

                    <form
                        onSubmit={unggah}
                        className="space-y-3 rounded-3xl border bg-card p-5 shadow-xs"
                    >
                        <h2 className="text-base font-bold text-navy-900">
                            Unggah dokumen
                        </h2>
                        <PilihFile
                            files={form.data.dokumen}
                            onChange={(files) => form.setData('dokumen', files)}
                            ekstensi={aturanDokumen.ekstensi}
                            maksKb={aturanDokumen.maks_kb}
                            errors={errorFile}
                        />
                        {form.errors.dokumen && (
                            <p className="text-sm font-medium text-destructive">
                                {form.errors.dokumen}
                            </p>
                        )}
                        <Button
                            type="submit"
                            className="w-full"
                            disabled={
                                form.processing ||
                                form.data.dokumen.length === 0
                            }
                        >
                            {form.processing && (
                                <LoaderCircle className="animate-spin" />
                            )}
                            Unggah{' '}
                            {form.data.dokumen.length > 0 &&
                                `${form.data.dokumen.length} dokumen`}
                        </Button>
                    </form>
                </div>
            )}
        </>
    );
}
