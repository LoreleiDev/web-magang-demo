import { Head, Link, useForm, usePage } from '@inertiajs/react';
import {
    ArrowLeft,
    Building2,
    GraduationCap,
    Info,
    LoaderCircle,
    UserRound,
} from 'lucide-react';
import type { FormEvent } from 'react';
import { FormField, FormSection } from '@/components/form-field';
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
import { cn } from '@/lib/utils';
import akunRoutes from '@/routes/superadmin/akun';

type RoleDikelola = 'guru' | 'siswa' | 'industri';

type DataAkun = {
    id: number;
    name: string;
    email: string;
    role: RoleDikelola;
    status_aktif: boolean;
    nip: string | null;
    program_keahlian: string | null;
    id_siswa: string | null;
    kelompok: string | null;
    unit_kerja: string | null;
    perusahaan_id: number | null;
    jabatan: string | null;
};

type Props = {
    akun: DataAkun | null;
    roleAwal: RoleDikelola;
    perusahaan: { id: number; nama: string }[];
};

const pilihanRole = [
    {
        role: 'siswa',
        label: 'Siswa',
        icon: UserRound,
        ket: 'Mengikuti pendampingan magang',
    },
    {
        role: 'guru',
        label: 'Guru',
        icon: GraduationCap,
        ket: 'Membimbing kelompok magang',
    },
    {
        role: 'industri',
        label: 'Industri',
        icon: Building2,
        ket: 'Pembimbing di perusahaan',
    },
] as const;

const TANPA_PROGRAM = '__kosong';

export default function AkunForm({ akun, roleAwal, perusahaan }: Props) {
    const { programKeahlian } = usePage().props;
    const edit = akun !== null;

    const form = useForm({
        role: roleAwal,
        name: akun?.name ?? '',
        email: akun?.email ?? '',
        password: '',
        nip: akun?.nip ?? '',
        program_keahlian: akun?.program_keahlian ?? '',
        id_siswa: akun?.id_siswa ?? '',
        perusahaan_id: akun?.perusahaan_id ? String(akun.perusahaan_id) : '',
        jabatan: akun?.jabatan ?? '',
    });
    const { data, setData, errors, processing } = form;

    const simpan = (e: FormEvent) => {
        e.preventDefault();

        if (edit) {
            form.transform(({ role: _role, ...rest }) => rest);
            form.put(akunRoutes.update(akun.id).url, { preserveScroll: true });
        } else {
            form.post(akunRoutes.store().url, { preserveScroll: true });
        }
    };

    return (
        <>
            <Head title={edit ? `Edit ${akun.name}` : 'Tambah akun'} />

            <Link
                href={akunRoutes.index({ query: { role: data.role } }).url}
                className="mb-4 inline-flex items-center gap-1.5 text-sm font-semibold text-navy-600 hover:text-navy-900"
            >
                <ArrowLeft className="size-4" /> Daftar akun
            </Link>

            <PageHeader
                title={edit ? `Edit akun ${akun.name}` : 'Tambah akun'}
                description={
                    edit
                        ? 'Kosongkan kata sandi jika tidak ingin menggantinya.'
                        : 'Akun login berbasis email. Berikan email dan kata sandi awal ini kepada pengguna.'
                }
            />

            <form
                onSubmit={simpan}
                className="rounded-3xl border bg-card p-5 shadow-xs sm:p-8"
            >
                <FormSection
                    title="Jenis akun"
                    description={
                        edit
                            ? 'Jenis akun tidak bisa diubah setelah dibuat.'
                            : 'Pilih peran pengguna.'
                    }
                >
                    <div className="grid gap-2 sm:grid-cols-3">
                        {pilihanRole.map((p) => {
                            const aktif = data.role === p.role;

                            return (
                                <button
                                    key={p.role}
                                    type="button"
                                    disabled={edit}
                                    onClick={() => setData('role', p.role)}
                                    aria-pressed={aktif}
                                    className={cn(
                                        'flex items-center gap-3 rounded-2xl border p-3 text-left transition-colors sm:flex-col sm:items-start',
                                        aktif
                                            ? 'border-navy-900 bg-navy-900 text-white shadow-md'
                                            : 'bg-card text-navy-900 hover:bg-navy-50',
                                        edit && !aktif && 'hidden',
                                        edit && 'cursor-default',
                                    )}
                                >
                                    <span
                                        className={cn(
                                            'grid size-9 shrink-0 place-items-center rounded-xl',
                                            aktif
                                                ? 'bg-white/10'
                                                : 'bg-navy-50 text-navy-600',
                                        )}
                                    >
                                        <p.icon className="size-5" />
                                    </span>
                                    <span>
                                        <span className="block text-sm font-bold">
                                            {p.label}
                                        </span>
                                        <span
                                            className={cn(
                                                'block text-xs',
                                                aktif
                                                    ? 'text-navy-200'
                                                    : 'text-muted-foreground',
                                            )}
                                        >
                                            {p.ket}
                                        </span>
                                    </span>
                                </button>
                            );
                        })}
                    </div>
                    {errors.role && (
                        <p className="text-sm font-medium text-destructive">
                            {errors.role}
                        </p>
                    )}
                </FormSection>

                <FormSection title="Data login">
                    <FormField
                        label="Nama lengkap"
                        htmlFor="name"
                        error={errors.name}
                        wajib
                    >
                        <Input
                            id="name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            aria-invalid={!!errors.name}
                            autoComplete="off"
                        />
                    </FormField>
                    <div className="grid gap-5 sm:grid-cols-2">
                        <FormField
                            label="Email"
                            htmlFor="email"
                            error={errors.email}
                            wajib
                        >
                            <Input
                                id="email"
                                type="email"
                                inputMode="email"
                                value={data.email}
                                onChange={(e) =>
                                    setData('email', e.target.value)
                                }
                                aria-invalid={!!errors.email}
                                autoComplete="off"
                            />
                        </FormField>
                        <FormField
                            label={edit ? 'Kata sandi baru' : 'Kata sandi awal'}
                            htmlFor="password"
                            error={errors.password}
                            hint="Minimal 8 karakter."
                            wajib={!edit}
                        >
                            <Input
                                id="password"
                                type="text"
                                value={data.password}
                                onChange={(e) =>
                                    setData('password', e.target.value)
                                }
                                aria-invalid={!!errors.password}
                                autoComplete="new-password"
                                placeholder={
                                    edit ? 'Biarkan kosong jika tetap' : ''
                                }
                            />
                        </FormField>
                    </div>
                </FormSection>

                {data.role === 'siswa' && (
                    <FormSection
                        title="Data siswa"
                        description="Perusahaan dan periode magang diambil dari kelompok magang."
                    >
                        <div className="grid gap-5 sm:grid-cols-2">
                            <FormField
                                label="ID siswa (NIS)"
                                htmlFor="id_siswa"
                                error={errors.id_siswa}
                                wajib
                            >
                                <Input
                                    id="id_siswa"
                                    inputMode="numeric"
                                    value={data.id_siswa}
                                    onChange={(e) =>
                                        setData('id_siswa', e.target.value)
                                    }
                                    aria-invalid={!!errors.id_siswa}
                                />
                            </FormField>
                            <FormField
                                label="Program keahlian"
                                error={errors.program_keahlian}
                                wajib
                            >
                                <PilihProgram
                                    value={data.program_keahlian}
                                    onChange={(v) =>
                                        setData('program_keahlian', v)
                                    }
                                    pilihan={programKeahlian}
                                    invalid={!!errors.program_keahlian}
                                />
                            </FormField>
                        </div>
                        {edit && (
                            <div className="flex gap-3 rounded-2xl bg-navy-50 p-4 text-sm text-navy-800">
                                <Info className="mt-0.5 size-4 shrink-0 text-navy-500" />
                                <div>
                                    <div>
                                        Kelompok:{' '}
                                        <strong>
                                            {akun.kelompok ??
                                                'Belum masuk kelompok'}
                                        </strong>
                                    </div>
                                    <div>
                                        Unit kerja:{' '}
                                        <strong>
                                            {akun.unit_kerja ??
                                                'Belum dipilih siswa'}
                                        </strong>
                                    </div>
                                    <div className="mt-1 text-xs text-muted-foreground">
                                        Kelompok diatur di menu Kelompok Magang.
                                    </div>
                                </div>
                            </div>
                        )}
                    </FormSection>
                )}

                {data.role === 'guru' && (
                    <FormSection
                        title="Data guru"
                        description="Program keahlian menentukan kompetensi dan materi yang bisa dikelola guru."
                    >
                        <div className="grid gap-5 sm:grid-cols-2">
                            <FormField
                                label="NIP"
                                htmlFor="nip"
                                error={errors.nip}
                                hint="Opsional."
                            >
                                <Input
                                    id="nip"
                                    inputMode="numeric"
                                    value={data.nip}
                                    onChange={(e) =>
                                        setData('nip', e.target.value)
                                    }
                                    aria-invalid={!!errors.nip}
                                />
                            </FormField>
                            <FormField
                                label="Program keahlian"
                                error={errors.program_keahlian}
                                hint="Wajib diisi jika guru akan membuat kompetensi dan materi."
                            >
                                <PilihProgram
                                    value={data.program_keahlian}
                                    onChange={(v) =>
                                        setData('program_keahlian', v)
                                    }
                                    pilihan={programKeahlian}
                                    bolehKosong
                                    invalid={!!errors.program_keahlian}
                                />
                            </FormField>
                        </div>
                    </FormSection>
                )}

                {data.role === 'industri' && (
                    <FormSection
                        title="Data pembimbing industri"
                        description="Akun industri wajib terhubung ke satu perusahaan."
                    >
                        <div className="grid gap-5 sm:grid-cols-2">
                            <FormField
                                label="Perusahaan"
                                error={errors.perusahaan_id}
                                wajib
                                hint={
                                    perusahaan.length === 0
                                        ? 'Belum ada perusahaan. Tambahkan dulu di menu Perusahaan.'
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
                                label="Jabatan"
                                htmlFor="jabatan"
                                error={errors.jabatan}
                                hint="Opsional."
                            >
                                <Input
                                    id="jabatan"
                                    value={data.jabatan}
                                    onChange={(e) =>
                                        setData('jabatan', e.target.value)
                                    }
                                    aria-invalid={!!errors.jabatan}
                                />
                            </FormField>
                        </div>
                    </FormSection>
                )}

                <div className="flex flex-col-reverse gap-2 border-t pt-6 sm:flex-row sm:justify-end">
                    <Button asChild variant="ghost">
                        <Link href={akunRoutes.index().url}>Batal</Link>
                    </Button>
                    <Button type="submit" disabled={processing}>
                        {processing && (
                            <LoaderCircle className="animate-spin" />
                        )}
                        {edit ? 'Simpan perubahan' : 'Buat akun'}
                    </Button>
                </div>
            </form>
        </>
    );
}

function PilihProgram({
    value,
    onChange,
    pilihan,
    bolehKosong = false,
    invalid,
}: {
    value: string;
    onChange: (v: string) => void;
    pilihan: Record<string, string>;
    bolehKosong?: boolean;
    invalid?: boolean;
}) {
    return (
        <Select
            value={value === '' && bolehKosong ? TANPA_PROGRAM : value}
            onValueChange={(v) => onChange(v === TANPA_PROGRAM ? '' : v)}
        >
            <SelectTrigger className="w-full" aria-invalid={invalid}>
                <SelectValue placeholder="Pilih program keahlian" />
            </SelectTrigger>
            <SelectContent>
                {bolehKosong && (
                    <SelectItem value={TANPA_PROGRAM}>Tidak ada</SelectItem>
                )}
                {Object.entries(pilihan).map(([kode, nama]) => (
                    <SelectItem key={kode} value={kode}>
                        {kode} · {nama}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );
}
