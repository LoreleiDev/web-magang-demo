import { Head, router, useForm, usePage } from '@inertiajs/react';
import {
    Building2,
    LoaderCircle,
    Plus,
    Trash2,
    TriangleAlert,
    UsersRound,
} from 'lucide-react';
import type { FormEvent } from 'react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { FormField } from '@/components/form-field';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import industri from '@/routes/industri';

type Unit = { nama: string; jumlah_siswa: number };

/**
 * Unit kerja perusahaan, dapat ditambah & dihapus langsung oleh pembimbing industri
 * (revisi 4 Okt 2026). Siswa memilih unit kerjanya dari daftar ini.
 */
export default function UnitKerja({
    perusahaan,
    unitKerja,
    maksUnit,
}: {
    perusahaan: string;
    unitKerja: Unit[];
    maksUnit: number;
}) {
    const form = useForm({ nama: '' });
    const penuh = unitKerja.length >= maksUnit;
    const errorHapus = (usePage().props.errors as Record<string, string>).hapus;

    const hapus = (nama: string) =>
        router.delete(industri.unitKerja.destroy().url, {
            data: { nama },
            preserveScroll: true,
        });

    const tambah = (e: FormEvent) => {
        e.preventDefault();
        form.post(industri.unitKerja.store().url, {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    };

    return (
        <>
            <Head title="Unit Kerja" />
            <PageHeader
                eyebrow={perusahaan}
                title="Unit kerja"
                description="Bagian atau divisi tempat siswa magang. Siswa memilih unit kerjanya sendiri dari daftar ini."
            />

            {errorHapus && (
                <div className="mb-5 flex items-start gap-3 rounded-2xl border border-gap-tinggi/30 bg-gap-tinggi-soft px-4 py-3 text-sm text-navy-900">
                    <TriangleAlert className="mt-0.5 size-4 shrink-0 text-gap-tinggi" />
                    {errorHapus}
                </div>
            )}

            <div className="grid gap-6 lg:grid-cols-[1fr_22rem] lg:items-start">
                <ul className="overflow-hidden rounded-3xl border bg-card shadow-xs">
                    {unitKerja.map((u) => (
                        <li
                            key={u.nama}
                            className="flex items-center gap-3 border-b px-5 py-4 last:border-none"
                        >
                            <span className="grid size-10 shrink-0 place-items-center rounded-xl bg-navy-50 text-navy-600">
                                <Building2 className="size-5" />
                            </span>
                            <span className="min-w-0 flex-1 text-sm font-semibold break-words text-navy-900">
                                {u.nama}
                            </span>
                            <span className="flex shrink-0 items-center gap-1.5 text-xs font-semibold text-navy-600">
                                <UsersRound className="size-3.5 text-navy-400" />
                                <span className="tabular-nums">
                                    {u.jumlah_siswa}
                                </span>{' '}
                                siswa
                            </span>
                            <TombolHapus
                                unit={u}
                                satuSatunya={unitKerja.length === 1}
                                onHapus={() => hapus(u.nama)}
                            />
                        </li>
                    ))}
                </ul>

                <form
                    onSubmit={tambah}
                    className="rounded-3xl border bg-card p-5 shadow-xs sm:p-6"
                >
                    <h2 className="text-base font-bold text-navy-900">
                        Tambah unit kerja
                    </h2>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Unit baru langsung bisa dipilih siswa.
                    </p>
                    <FormField
                        label="Nama unit kerja"
                        htmlFor="nama"
                        error={form.errors.nama}
                        hint={`${unitKerja.length} dari maksimal ${maksUnit} unit`}
                        wajib
                        className="mt-5"
                    >
                        <Input
                            id="nama"
                            value={form.data.nama}
                            onChange={(e) =>
                                form.setData('nama', e.target.value)
                            }
                            placeholder="Contoh: Network Operation Center"
                            maxLength={100}
                            disabled={penuh}
                            aria-invalid={!!form.errors.nama}
                        />
                    </FormField>
                    <Button
                        type="submit"
                        className="mt-5 w-full"
                        disabled={
                            form.processing || penuh || !form.data.nama.trim()
                        }
                    >
                        {form.processing ? (
                            <LoaderCircle className="animate-spin" />
                        ) : (
                            <Plus />
                        )}
                        Tambah unit
                    </Button>
                    <p className="mt-3 text-xs text-muted-foreground">
                        Unit kerja yang masih dipilih siswa tidak bisa dihapus.
                    </p>
                </form>
            </div>
        </>
    );
}

/**
 * Hapus unit kerja. Unit yang masih dipilih siswa, atau unit satu-satunya, tidak
 * bisa dihapus (dicek juga di backend).
 */
function TombolHapus({
    unit,
    satuSatunya,
    onHapus,
}: {
    unit: Unit;
    satuSatunya: boolean;
    onHapus: () => void;
}) {
    const alasan =
        unit.jumlah_siswa > 0
            ? `Masih dipilih ${unit.jumlah_siswa} siswa`
            : satuSatunya
              ? 'Perusahaan harus punya minimal satu unit kerja'
              : null;

    if (alasan) {
        return (
            <Button
                variant="ghost"
                size="icon-sm"
                disabled
                title={alasan}
                aria-label={`${unit.nama} tidak bisa dihapus: ${alasan}`}
            >
                <Trash2 />
            </Button>
        );
    }

    return (
        <ConfirmDialog
            trigger={
                <Button
                    variant="ghost"
                    size="icon-sm"
                    className="text-gap-tinggi hover:bg-gap-tinggi-soft hover:text-gap-tinggi"
                    aria-label={`Hapus ${unit.nama}`}
                >
                    <Trash2 />
                </Button>
            }
            title={`Hapus unit kerja ${unit.nama}?`}
            description="Unit ini tidak akan muncul lagi di pilihan unit kerja siswa."
            confirmLabel="Hapus"
            destructive
            onConfirm={onHapus}
        />
    );
}
