import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, ArrowRightLeft, LoaderCircle, Search } from 'lucide-react';
import type { FormEvent } from 'react';
import { useMemo, useState } from 'react';
import { FormField, FormSection } from '@/components/form-field';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { cn } from '@/lib/utils';
import kelompokRoutes from '@/routes/superadmin/kelompok';

type Siswa = {
    id: number;
    nama: string;
    id_siswa: string | null;
    program_keahlian: string | null;
    kelompok_id: number | null;
    kelompok_nama: string | null;
};

type Props = {
    kelompok: {
        id: number;
        nama_kelompok: string;
        perusahaan_id: number;
        guru_pembimbing_id: number;
        periode_mulai: string;
        periode_selesai: string;
        siswa_ids: number[];
    } | null;
    perusahaan: { id: number; nama: string }[];
    guru: {
        id: number;
        nama: string;
        program_keahlian: string | null;
        jumlah_kelompok: number;
    }[];
    siswa: Siswa[];
};

export default function KelompokForm({
    kelompok,
    perusahaan,
    guru,
    siswa,
}: Props) {
    const edit = kelompok !== null;

    const form = useForm({
        nama_kelompok: kelompok?.nama_kelompok ?? '',
        perusahaan_id: kelompok ? String(kelompok.perusahaan_id) : '',
        guru_pembimbing_id: kelompok ? String(kelompok.guru_pembimbing_id) : '',
        periode_mulai: kelompok?.periode_mulai ?? '',
        periode_selesai: kelompok?.periode_selesai ?? '',
        siswa_ids: kelompok?.siswa_ids ?? [],
    });
    const { data, setData, errors, processing } = form;

    const simpan = (e: FormEvent) => {
        e.preventDefault();

        if (edit) {
            form.put(kelompokRoutes.update(kelompok.id).url, {
                preserveScroll: true,
            });
        } else {
            form.post(kelompokRoutes.store().url, { preserveScroll: true });
        }
    };

    return (
        <>
            <Head
                title={
                    edit ? `Edit ${kelompok.nama_kelompok}` : 'Buat kelompok'
                }
            />

            <Link
                href={kelompokRoutes.index().url}
                className="mb-4 inline-flex items-center gap-1.5 text-sm font-semibold text-navy-600 hover:text-navy-900"
            >
                <ArrowLeft className="size-4" /> Daftar kelompok
            </Link>

            <PageHeader
                title={edit ? kelompok.nama_kelompok : 'Buat kelompok magang'}
                description="Isi sesuai proposal magang siswa."
            />

            <form
                onSubmit={simpan}
                className="rounded-3xl border bg-card p-5 shadow-xs sm:p-8"
            >
                <FormSection title="Data kelompok">
                    <FormField
                        label="Nama kelompok"
                        htmlFor="nama_kelompok"
                        error={errors.nama_kelompok}
                        wajib
                    >
                        <Input
                            id="nama_kelompok"
                            value={data.nama_kelompok}
                            onChange={(e) =>
                                setData('nama_kelompok', e.target.value)
                            }
                            placeholder="Contoh: PKL TKJ 2026 - PT Maju Jaya"
                            aria-invalid={!!errors.nama_kelompok}
                        />
                    </FormField>
                    <div className="grid gap-5 sm:grid-cols-2">
                        <FormField
                            label="Perusahaan tujuan"
                            error={errors.perusahaan_id}
                            wajib
                            hint={
                                edit
                                    ? 'Jika perusahaan diganti, siswa harus memilih unit kerja lagi.'
                                    : undefined
                            }
                        >
                            <Select
                                value={data.perusahaan_id}
                                onValueChange={(v) =>
                                    setData('perusahaan_id', v)
                                }
                            >
                                <SelectTrigger
                                    className="w-full"
                                    aria-invalid={!!errors.perusahaan_id}
                                >
                                    <SelectValue placeholder="Pilih perusahaan" />
                                </SelectTrigger>
                                <SelectContent>
                                    {perusahaan.map((p) => (
                                        <SelectItem
                                            key={p.id}
                                            value={String(p.id)}
                                        >
                                            {p.nama}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </FormField>
                        <FormField
                            label="Guru pembimbing"
                            error={errors.guru_pembimbing_id}
                            wajib
                            hint="Satu kelompok hanya satu guru. Satu guru boleh membimbing beberapa kelompok."
                        >
                            <Select
                                value={data.guru_pembimbing_id}
                                onValueChange={(v) =>
                                    setData('guru_pembimbing_id', v)
                                }
                            >
                                <SelectTrigger
                                    className="w-full"
                                    aria-invalid={!!errors.guru_pembimbing_id}
                                >
                                    <SelectValue placeholder="Pilih guru" />
                                </SelectTrigger>
                                <SelectContent>
                                    {guru.map((g) => (
                                        <SelectItem
                                            key={g.id}
                                            value={String(g.id)}
                                        >
                                            {g.nama}
                                            <span className="text-xs text-muted-foreground">
                                                {g.program_keahlian
                                                    ? ` · ${g.program_keahlian}`
                                                    : ''}{' '}
                                                · {g.jumlah_kelompok} kelompok
                                            </span>
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </FormField>
                    </div>
                    <div className="grid grid-cols-2 gap-3 sm:gap-5">
                        <FormField
                            label="Tanggal mulai"
                            htmlFor="periode_mulai"
                            error={errors.periode_mulai}
                            wajib
                        >
                            <Input
                                id="periode_mulai"
                                type="date"
                                value={data.periode_mulai}
                                onChange={(e) =>
                                    setData('periode_mulai', e.target.value)
                                }
                                aria-invalid={!!errors.periode_mulai}
                            />
                        </FormField>
                        <FormField
                            label="Tanggal selesai"
                            htmlFor="periode_selesai"
                            error={errors.periode_selesai}
                            wajib
                        >
                            <Input
                                id="periode_selesai"
                                type="date"
                                min={data.periode_mulai || undefined}
                                value={data.periode_selesai}
                                onChange={(e) =>
                                    setData('periode_selesai', e.target.value)
                                }
                                aria-invalid={!!errors.periode_selesai}
                            />
                        </FormField>
                    </div>
                </FormSection>

                <FormSection
                    title="Siswa"
                    description="Memilih siswa yang sudah ada di kelompok lain akan memindahkannya ke kelompok ini."
                >
                    <PilihSiswa
                        siswa={siswa}
                        kelompokId={kelompok?.id ?? null}
                        terpilih={data.siswa_ids}
                        onChange={(ids) => setData('siswa_ids', ids)}
                    />
                    {(errors.siswa_ids ||
                        Object.keys(errors).some((k) =>
                            k.startsWith('siswa_ids.'),
                        )) && (
                        <p className="text-sm font-medium text-destructive">
                            {errors.siswa_ids ??
                                'Ada siswa yang tidak valid. Muat ulang halaman lalu pilih lagi.'}
                        </p>
                    )}
                </FormSection>

                <div className="flex flex-col-reverse gap-2 border-t pt-6 sm:flex-row sm:justify-end">
                    <Button asChild variant="ghost">
                        <Link href={kelompokRoutes.index().url}>Batal</Link>
                    </Button>
                    <Button type="submit" disabled={processing}>
                        {processing && (
                            <LoaderCircle className="animate-spin" />
                        )}
                        {edit ? 'Simpan perubahan' : 'Buat kelompok'}
                    </Button>
                </div>
            </form>
        </>
    );
}

function PilihSiswa({
    siswa,
    kelompokId,
    terpilih,
    onChange,
}: {
    siswa: Siswa[];
    kelompokId: number | null;
    terpilih: number[];
    onChange: (ids: number[]) => void;
}) {
    const [cari, setCari] = useState('');
    const [program, setProgram] = useState<string | null>(null);
    const [hanyaTerpilih, setHanyaTerpilih] = useState(false);

    const daftarProgram = useMemo(
        () =>
            [...new Set(siswa.map((s) => s.program_keahlian))].filter(
                (p): p is string => p !== null,
            ),
        [siswa],
    );

    const tampil = siswa.filter((s) => {
        const kata = cari.toLowerCase();

        return (
            (!hanyaTerpilih || terpilih.includes(s.id)) &&
            (program === null || s.program_keahlian === program) &&
            (kata === '' ||
                s.nama.toLowerCase().includes(kata) ||
                (s.id_siswa ?? '').includes(kata))
        );
    });

    const dipindah = siswa.filter(
        (s) =>
            terpilih.includes(s.id) &&
            s.kelompok_id !== null &&
            s.kelompok_id !== kelompokId,
    ).length;

    const toggle = (id: number) =>
        onChange(
            terpilih.includes(id)
                ? terpilih.filter((x) => x !== id)
                : [...terpilih, id],
        );

    return (
        <div className="space-y-3">
            <div className="flex flex-col gap-2 sm:flex-row">
                <div className="relative flex-1">
                    <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        type="search"
                        value={cari}
                        onChange={(e) => setCari(e.target.value)}
                        placeholder="Cari nama atau NIS"
                        className="pl-9"
                        aria-label="Cari siswa"
                    />
                </div>
                <div className="flex gap-1.5 overflow-x-auto">
                    {[null, ...daftarProgram].map((p) => (
                        <button
                            key={p ?? 'semua'}
                            type="button"
                            onClick={() => setProgram(p)}
                            className={cn(
                                'h-10 shrink-0 rounded-lg px-3 text-sm font-semibold',
                                program === p
                                    ? 'bg-navy-900 text-white'
                                    : 'border bg-card text-navy-700 hover:bg-navy-50',
                            )}
                        >
                            {p ?? 'Semua'}
                        </button>
                    ))}
                </div>
            </div>

            <div className="flex flex-wrap items-center justify-between gap-2 rounded-xl bg-navy-50/70 px-3 py-2 text-sm">
                <span className="font-semibold text-navy-900">
                    {terpilih.length} siswa dipilih
                    {dipindah > 0 && (
                        <span className="ml-2 inline-flex items-center gap-1 font-medium text-gap-sedang">
                            <ArrowRightLeft className="size-3.5" />
                            {dipindah} akan dipindahkan
                        </span>
                    )}
                </span>
                <label className="flex items-center gap-2 text-navy-700">
                    <Checkbox
                        checked={hanyaTerpilih}
                        onCheckedChange={(v) => setHanyaTerpilih(v === true)}
                    />
                    Tampilkan yang dipilih saja
                </label>
            </div>

            <ul className="max-h-[26rem] divide-y overflow-y-auto rounded-2xl border">
                {tampil.length === 0 && (
                    <li className="px-4 py-6 text-center text-sm text-muted-foreground">
                        Tidak ada siswa yang cocok.
                    </li>
                )}
                {tampil.map((s) => {
                    const dipilih = terpilih.includes(s.id);
                    const diKelompokLain =
                        s.kelompok_id !== null && s.kelompok_id !== kelompokId;

                    return (
                        <li key={s.id}>
                            <label
                                className={cn(
                                    'flex cursor-pointer items-center gap-3 px-4 py-3 transition-colors',
                                    dipilih
                                        ? 'bg-navy-50/80'
                                        : 'hover:bg-navy-50/40',
                                )}
                            >
                                <Checkbox
                                    checked={dipilih}
                                    onCheckedChange={() => toggle(s.id)}
                                />
                                <div className="min-w-0 flex-1">
                                    <div className="truncate text-sm font-semibold text-navy-900">
                                        {s.nama}
                                    </div>
                                    <div className="text-xs text-muted-foreground">
                                        NIS {s.id_siswa} · {s.program_keahlian}
                                    </div>
                                </div>
                                {diKelompokLain && (
                                    <span
                                        className={cn(
                                            'max-w-[45%] truncate rounded-full px-2 py-0.5 text-[11px] font-semibold',
                                            dipilih
                                                ? 'bg-gap-sedang-soft text-navy-900'
                                                : 'bg-navy-100/70 text-navy-600',
                                        )}
                                        title={s.kelompok_nama ?? ''}
                                    >
                                        {dipilih ? 'Pindah dari ' : ''}
                                        {s.kelompok_nama}
                                    </span>
                                )}
                            </label>
                        </li>
                    );
                })}
            </ul>
        </div>
    );
}
