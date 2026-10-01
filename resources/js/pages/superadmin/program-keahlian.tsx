import { Head, router, useForm } from '@inertiajs/react';
import {
    GraduationCap,
    LoaderCircle,
    Pencil,
    Plus,
    Trash2,
} from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { FormField } from '@/components/form-field';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import programRoutes from '@/routes/superadmin/program-keahlian';

type Program = {
    id: number;
    kode: string;
    nama: string;
    jumlah_siswa: number;
    jumlah_guru: number;
    jumlah_kompetensi: number;
    bisa_dihapus: boolean;
};

export default function ProgramKeahlianIndex({
    program,
}: {
    program: Program[];
}) {
    const form = useForm({ kode: '', nama: '' });

    const tambah = (e: FormEvent) => {
        e.preventDefault();
        form.post(programRoutes.store().url, {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    };

    return (
        <>
            <Head title="Program Keahlian" />
            <PageHeader
                eyebrow="Panel Superadmin"
                title="Program keahlian"
                description="Dipilih saat membuat akun siswa dan guru. Menentukan kompetensi, materi, dan dokumen sekolah yang dilihat siswa."
            />

            <div className="grid gap-6 lg:grid-cols-[20rem_1fr] lg:items-start">
                <form
                    onSubmit={tambah}
                    className="space-y-4 rounded-3xl border bg-card p-5 shadow-xs"
                >
                    <h2 className="text-base font-bold text-navy-900">
                        Tambah program
                    </h2>
                    <FormField
                        label="Kode"
                        htmlFor="kode"
                        error={form.errors.kode}
                        hint="Singkatan, contoh TKJ. Tidak bisa diubah setelah disimpan."
                        wajib
                    >
                        <Input
                            id="kode"
                            value={form.data.kode}
                            onChange={(e) =>
                                form.setData(
                                    'kode',
                                    e.target.value.toUpperCase(),
                                )
                            }
                            maxLength={20}
                            aria-invalid={!!form.errors.kode}
                        />
                    </FormField>
                    <FormField
                        label="Nama program"
                        htmlFor="nama"
                        error={form.errors.nama}
                        wajib
                    >
                        <Input
                            id="nama"
                            value={form.data.nama}
                            onChange={(e) =>
                                form.setData('nama', e.target.value)
                            }
                            aria-invalid={!!form.errors.nama}
                        />
                    </FormField>
                    <Button
                        type="submit"
                        className="w-full"
                        disabled={form.processing}
                    >
                        {form.processing ? (
                            <LoaderCircle className="animate-spin" />
                        ) : (
                            <Plus />
                        )}
                        Tambah program
                    </Button>
                </form>

                <ul className="overflow-hidden rounded-3xl border bg-card shadow-xs">
                    {program.length === 0 && (
                        <li className="px-5 py-10 text-center text-sm text-muted-foreground">
                            Belum ada program keahlian.
                        </li>
                    )}
                    {program.map((p) => (
                        <li
                            key={p.id}
                            className="flex items-center gap-4 border-b px-5 py-4 last:border-none"
                        >
                            <span className="grid h-11 min-w-11 shrink-0 place-items-center rounded-xl bg-navy-900 px-2 text-sm font-extrabold text-white">
                                {p.kode}
                            </span>
                            <div className="min-w-0 flex-1">
                                <div className="font-semibold text-navy-900">
                                    {p.nama}
                                </div>
                                <div className="mt-0.5 text-xs text-muted-foreground">
                                    {p.jumlah_siswa} siswa · {p.jumlah_guru}{' '}
                                    guru · {p.jumlah_kompetensi} kompetensi
                                </div>
                            </div>
                            <div className="flex shrink-0 gap-1">
                                <EditProgram program={p} />
                                <ConfirmDialog
                                    trigger={
                                        <Button
                                            variant="ghost"
                                            size="icon-sm"
                                            disabled={!p.bisa_dihapus}
                                            aria-label={`Hapus ${p.kode}`}
                                            title={
                                                p.bisa_dihapus
                                                    ? undefined
                                                    : 'Masih dipakai, tidak bisa dihapus'
                                            }
                                        >
                                            <Trash2 />
                                        </Button>
                                    }
                                    title={`Hapus program ${p.kode}?`}
                                    description="Program ini tidak akan bisa dipilih lagi."
                                    confirmLabel="Hapus"
                                    destructive
                                    onConfirm={() =>
                                        router.delete(
                                            programRoutes.destroy(p.id).url,
                                            { preserveScroll: true },
                                        )
                                    }
                                />
                            </div>
                        </li>
                    ))}
                </ul>
            </div>
        </>
    );
}

function EditProgram({ program }: { program: Program }) {
    const [terbuka, setTerbuka] = useState(false);
    const form = useForm({ nama: program.nama });

    const simpan = (e: FormEvent) => {
        e.preventDefault();
        form.put(programRoutes.update(program.id).url, {
            preserveScroll: true,
            onSuccess: () => setTerbuka(false),
        });
    };

    return (
        <Dialog open={terbuka} onOpenChange={setTerbuka}>
            <DialogTrigger asChild>
                <Button
                    variant="ghost"
                    size="icon-sm"
                    aria-label={`Edit ${program.kode}`}
                >
                    <Pencil />
                </Button>
            </DialogTrigger>
            <DialogContent className="rounded-3xl">
                <form onSubmit={simpan} className="space-y-5">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <GraduationCap className="size-5 text-navy-500" />
                            Edit program {program.kode}
                        </DialogTitle>
                        <DialogDescription>
                            Kode program tidak bisa diubah.
                        </DialogDescription>
                    </DialogHeader>
                    <FormField
                        label="Nama program"
                        htmlFor={`nama-${program.id}`}
                        error={form.errors.nama}
                    >
                        <Input
                            id={`nama-${program.id}`}
                            value={form.data.nama}
                            onChange={(e) =>
                                form.setData('nama', e.target.value)
                            }
                        />
                    </FormField>
                    <DialogFooter>
                        <Button type="submit" disabled={form.processing}>
                            Simpan
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
