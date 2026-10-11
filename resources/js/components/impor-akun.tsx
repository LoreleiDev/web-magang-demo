import { useForm, usePage } from '@inertiajs/react';
import {
    CircleCheck,
    Download,
    FileSpreadsheet,
    LoaderCircle,
    TriangleAlert,
    Upload,
    X,
} from 'lucide-react';
import { useRef } from 'react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import akunRoutes from '@/routes/superadmin/akun';
import type { HasilImporAkun } from '@/types/impor';

type RoleImpor = 'guru' | 'siswa' | 'industri';

const keteranganPassword: Record<RoleImpor, string> = {
    siswa: 'Password awal setiap siswa = NIS-nya.',
    guru: 'Password awal guru = NIP; jika NIP kosong, password dibuat acak.',
    industri: 'Password awal pembimbing industri dibuat acak oleh sistem.',
};

/**
 * Tambah banyak akun sekaligus dari file Excel (.xlsx) atau CSV sesuai template.
 * Tidak memakai elemen <form> karena dipasang di dalam form tambah akun.
 */
export function ImporAkun({ role }: { role: RoleImpor }) {
    const hasil = usePage().flash.hasilImpor;
    const input = useRef<HTMLInputElement>(null);
    const form = useForm<{ role: RoleImpor; file: File | null }>({
        role,
        file: null,
    });

    const kirim = () => {
        form.transform((data) => ({ ...data, role }));
        form.post(akunRoutes.impor.store().url, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                form.reset('file');

                if (input.current) {
                    input.current.value = '';
                }
            },
        });
    };

    return (
        <div className="space-y-6">
            <ol className="grid gap-3 sm:grid-cols-2">
                <li className="rounded-2xl border bg-navy-50/50 p-4">
                    <p className="text-xs font-bold tracking-wide text-hijau-600 uppercase">
                        Langkah 1
                    </p>
                    <p className="mt-1 text-sm font-semibold text-navy-900">
                        Unduh template, lalu isi datanya
                    </p>
                    <p className="mt-1 text-xs text-muted-foreground">
                        Lembar “Petunjuk” di template menjelaskan setiap kolom.{' '}
                        {keteranganPassword[role]}
                    </p>
                    <Button
                        asChild
                        variant="outline"
                        size="sm"
                        className="mt-3"
                    >
                        <a href={akunRoutes.impor.template(role).url} download>
                            <Download />
                            Template {role}
                        </a>
                    </Button>
                </li>
                <li className="rounded-2xl border bg-navy-50/50 p-4">
                    <p className="text-xs font-bold tracking-wide text-hijau-600 uppercase">
                        Langkah 2
                    </p>
                    <p className="mt-1 text-sm font-semibold text-navy-900">
                        Unggah file yang sudah diisi
                    </p>
                    <p className="mt-1 text-xs text-muted-foreground">
                        Format .xlsx atau .csv, maks. 2 MB (500 akun). Jika ada
                        baris yang salah, tidak ada akun yang dibuat.
                    </p>
                </li>
            </ol>

            <div>
                <input
                    ref={input}
                    id="file-impor"
                    type="file"
                    accept=".xlsx,.csv"
                    className="sr-only"
                    onChange={(e) =>
                        form.setData('file', e.target.files?.[0] ?? null)
                    }
                />
                {form.data.file ? (
                    <div className="flex items-center gap-3 rounded-2xl border bg-card px-4 py-3">
                        <FileSpreadsheet className="size-5 shrink-0 text-hijau-600" />
                        <span className="min-w-0 flex-1 truncate text-sm font-semibold text-navy-900">
                            {form.data.file.name}
                        </span>
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon-sm"
                            aria-label="Batal pilih file"
                            onClick={() => {
                                form.setData('file', null);

                                if (input.current) {
                                    input.current.value = '';
                                }
                            }}
                        >
                            <X />
                        </Button>
                    </div>
                ) : (
                    <label
                        htmlFor="file-impor"
                        className="flex cursor-pointer flex-col items-center gap-2 rounded-2xl border-2 border-dashed border-navy-200 px-4 py-8 text-center hover:bg-navy-50/60"
                    >
                        <span className="grid size-11 place-items-center rounded-full bg-white shadow-xs ring-1 ring-navy-100">
                            <Upload className="size-5 text-navy-600" />
                        </span>
                        <span className="text-sm font-semibold text-navy-900">
                            Pilih file Excel atau CSV
                        </span>
                        <span className="text-xs text-muted-foreground">
                            .xlsx atau .csv · maks. 2 MB
                        </span>
                    </label>
                )}
                {form.errors.file && (
                    <p className="mt-2 text-sm font-medium text-destructive">
                        {form.errors.file}
                    </p>
                )}
            </div>

            {hasil && hasil.role === role && <HasilImpor hasil={hasil} />}

            <div className="flex flex-col-reverse gap-2 border-t pt-6 sm:flex-row sm:justify-end">
                <Button
                    type="button"
                    disabled={!form.data.file || form.processing}
                    onClick={kirim}
                >
                    {form.processing ? (
                        <LoaderCircle className="animate-spin" />
                    ) : (
                        <Upload />
                    )}
                    Impor akun
                </Button>
            </div>
        </div>
    );
}

function HasilImpor({ hasil }: { hasil: HasilImporAkun }) {
    if (!hasil.berhasil) {
        return (
            <div className="rounded-2xl border border-gap-tinggi/30 bg-gap-tinggi-soft p-4">
                <p className="flex items-start gap-2 text-sm font-semibold text-navy-900">
                    <TriangleAlert className="mt-0.5 size-4 shrink-0 text-gap-tinggi" />
                    {hasil.error.length} dari {hasil.total} baris perlu
                    diperbaiki. Belum ada akun yang dibuat.
                </p>
                <ul className="mt-3 max-h-72 space-y-2 overflow-y-auto">
                    {hasil.error.map((e) => (
                        <li
                            key={e.baris}
                            className="rounded-xl bg-white/80 px-3 py-2 text-sm"
                        >
                            <span className="font-bold text-navy-900">
                                Baris {e.baris}:
                            </span>{' '}
                            <span className="text-navy-700">
                                {e.pesan.join(' ')}
                            </span>
                        </li>
                    ))}
                </ul>
            </div>
        );
    }

    const unduh = () => {
        const isi = [
            ['nama', 'email', 'password_awal'],
            ...hasil.akun.map((a) => [a.nama, a.email, a.password]),
        ]
            .map((baris) =>
                baris
                    // Teks diawali = + - @ dianggap formula oleh Excel; beri ' agar tetap teks.
                    .map((v) => (/^[=+\-@\t\r]/.test(v) ? `'${v}` : v))
                    .map((v) => `"${v.replaceAll('"', '""')}"`)
                    .join(','),
            )
            .join('\n');
        const url = URL.createObjectURL(
            new Blob([`﻿${isi}`], { type: 'text/csv;charset=utf-8' }),
        );
        const a = document.createElement('a');
        a.href = url;
        a.download = `akun-${hasil.role}-baru.csv`;
        a.click();
        URL.revokeObjectURL(url);
    };

    return (
        <div className="rounded-2xl border border-hijau-100 bg-hijau-50 p-4">
            <div className="flex flex-wrap items-start justify-between gap-3">
                <p className="flex items-start gap-2 text-sm font-semibold text-navy-900">
                    <CircleCheck className="mt-0.5 size-4 shrink-0 text-hijau-600" />
                    {hasil.total} akun berhasil dibuat.
                </p>
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={unduh}
                >
                    <Download />
                    Unduh daftar password
                </Button>
            </div>
            <p className="mt-1 pl-6 text-xs text-navy-700">
                Daftar ini hanya tampil sekali. Unduh lalu bagikan password ke
                pemilik akun secara pribadi.
            </p>
            <ul className="mt-3 max-h-80 divide-y overflow-y-auto rounded-xl bg-white">
                {hasil.akun.map((a) => (
                    <li
                        key={a.email}
                        className="flex flex-col gap-1 px-3 py-2.5 sm:flex-row sm:items-center sm:gap-4"
                    >
                        <div className="min-w-0 flex-1">
                            <p className="truncate text-sm font-semibold text-navy-900">
                                {a.nama}
                            </p>
                            <p className="truncate text-xs text-navy-600">
                                {a.email}
                            </p>
                        </div>
                        <p className="shrink-0 text-sm">
                            <code className="rounded bg-navy-50 px-1.5 py-0.5 text-navy-900">
                                {a.password}
                            </code>
                            <span
                                className={cn(
                                    'ml-1.5 text-xs',
                                    a.sumber_password === 'Acak'
                                        ? 'text-gap-tinggi'
                                        : 'text-muted-foreground',
                                )}
                            >
                                {a.sumber_password}
                            </span>
                        </p>
                    </li>
                ))}
            </ul>
        </div>
    );
}
