import { Head, useForm } from '@inertiajs/react';
import {
    ArrowRight,
    Building2,
    CircleAlert,
    GraduationCap,
    IdCard,
    LoaderCircle,
    Lock,
    LogOut,
    UserRound,
} from 'lucide-react';
import type { FormEvent, ReactNode } from 'react';
import { AppLogo } from '@/components/app-logo';
import { GapBadge, StatusBadge } from '@/components/kompetensi-indikator';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { cn } from '@/lib/utils';
import { konfirmasiKeluar } from '@/lib/konfirmasi-keluar';
import siswa from '@/routes/siswa';
import type { BarisKompetensi } from '@/types/kompetensi';
import type { KonteksSiswa } from '@/types/siswa';

type Props = {
    konteks: KonteksSiswa;
    terdaftar: boolean;
    daftarUnitKerja: string[];
    kompetensi: BarisKompetensi[];
    kompetensiTerpilih: number | null;
};

/**
 * Halaman awal siswa (CLAUDE.md bagian 4.1): data terkunci, unit kerja (sekali),
 * dan kompetensi yang dijalani (setiap login).
 */
export default function MulaiPendampingan({
    konteks,
    terdaftar,
    daftarUnitKerja,
    kompetensi,
    kompetensiTerpilih,
}: Props) {
    const unitSudahDipilih = konteks.unit_kerja !== null;
    const form = useForm({
        unit_kerja: '',
        kompetensi_id: kompetensiTerpilih ? String(kompetensiTerpilih) : '',
    });

    const mulai = (e: FormEvent) => {
        e.preventDefault();
        form.transform((data) =>
            unitSudahDipilih ? { kompetensi_id: data.kompetensi_id } : data,
        );
        form.post(siswa.mulai.store().url);
    };

    return (
        <>
            <Head title="Mulai Pendampingan" />
            <div className="min-h-dvh bg-background">
                <header className="bg-navy-900 tekstur-titik px-4 pt-6 pb-24 text-white sm:px-8">
                    <div className="mx-auto flex max-w-3xl items-center justify-between">
                        <AppLogo tone="terang" />
                        <button
                            type="button"
                            onClick={() => void konfirmasiKeluar()}
                            className="flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-semibold text-navy-200 hover:bg-white/10 hover:text-white"
                        >
                            <LogOut className="size-4" />
                            Keluar
                        </button>
                    </div>
                    <div className="mx-auto mt-10 max-w-3xl">
                        <p className="text-xs font-bold tracking-[0.2em] text-hijau-500 uppercase">
                            Pendampingan magang
                        </p>
                        <h1 className="mt-2 text-3xl font-extrabold tracking-tight text-balance sm:text-4xl">
                            Halo, {konteks.nama}
                        </h1>
                        <p className="mt-2 max-w-xl text-navy-200">
                            Periksa data magang Anda, lalu pilih unit kerja dan
                            kompetensi utama. Cukup sekali saja.
                        </p>
                    </div>
                </header>

                <main className="mx-auto -mt-16 max-w-3xl px-4 pb-12 sm:px-8">
                    {!terdaftar ? (
                        <section className="rounded-3xl border bg-card p-6 shadow-lg sm:p-8">
                            <span className="grid size-12 place-items-center rounded-2xl bg-gap-sedang-soft text-gap-sedang">
                                <CircleAlert className="size-6" />
                            </span>
                            <h2 className="mt-4 text-xl font-extrabold text-navy-900">
                                Anda belum terdaftar di kelompok magang
                            </h2>
                            <p className="mt-2 text-muted-foreground">
                                Pendampingan bisa dimulai setelah sekolah
                                memasukkan Anda ke kelompok magang. Silakan
                                hubungi guru pembimbing atau admin sekolah.
                            </p>
                        </section>
                    ) : (
                        <form
                            onSubmit={mulai}
                            className="space-y-5 rounded-3xl border bg-card p-5 shadow-lg sm:p-8"
                        >
                            <section>
                                <h2 className="text-sm font-bold text-navy-900">
                                    Data pendampingan
                                </h2>
                                <dl className="mt-3 grid gap-2 sm:grid-cols-2">
                                    <Terkunci
                                        icon={UserRound}
                                        label="Nama siswa"
                                    >
                                        {konteks.nama}
                                    </Terkunci>
                                    <Terkunci icon={IdCard} label="ID siswa">
                                        {konteks.id_siswa}
                                    </Terkunci>
                                    <Terkunci
                                        icon={GraduationCap}
                                        label="Program keahlian"
                                    >
                                        {konteks.program_keahlian_nama}
                                    </Terkunci>
                                    <Terkunci
                                        icon={Building2}
                                        label="Nama industri"
                                    >
                                        {konteks.perusahaan}
                                    </Terkunci>
                                </dl>
                            </section>

                            <section>
                                <label
                                    htmlFor="unit_kerja"
                                    className="text-sm font-bold text-navy-900"
                                >
                                    Unit / bagian kerja
                                </label>
                                {unitSudahDipilih ? (
                                    <div className="mt-2">
                                        <Terkunci
                                            icon={Building2}
                                            label="Sudah dipilih"
                                        >
                                            {konteks.unit_kerja}
                                        </Terkunci>
                                    </div>
                                ) : (
                                    <>
                                        <p className="text-xs text-muted-foreground">
                                            Pilih bagian tempat Anda
                                            ditempatkan. Bisa diganti nanti dari
                                            Dashboard.
                                        </p>
                                        <Select
                                            value={form.data.unit_kerja}
                                            onValueChange={(v) =>
                                                form.setData('unit_kerja', v)
                                            }
                                        >
                                            <SelectTrigger
                                                id="unit_kerja"
                                                className="mt-2 w-full"
                                                aria-invalid={
                                                    !!form.errors.unit_kerja
                                                }
                                            >
                                                <SelectValue placeholder="Pilih unit kerja" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {daftarUnitKerja.map((u) => (
                                                    <SelectItem
                                                        key={u}
                                                        value={u}
                                                    >
                                                        {u}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        {form.errors.unit_kerja && (
                                            <p className="mt-1 text-sm font-medium text-destructive">
                                                {form.errors.unit_kerja}
                                            </p>
                                        )}
                                    </>
                                )}
                            </section>

                            <section>
                                <h2 className="text-sm font-bold text-navy-900">
                                    Kompetensi utama
                                </h2>
                                <p className="text-xs text-muted-foreground">
                                    Kompetensi yang ingin Anda dalami. Materinya
                                    tampil paling atas dan bisa diganti nanti di
                                    Peta Kompetensi.
                                </p>
                                {kompetensi.length === 0 ? (
                                    <p className="mt-3 rounded-2xl bg-navy-50 px-4 py-3 text-sm text-navy-700">
                                        Guru belum membuat kompetensi untuk
                                        program keahlian Anda. Hubungi guru
                                        pembimbing.
                                    </p>
                                ) : (
                                    <div
                                        role="radiogroup"
                                        aria-label="Kompetensi"
                                        className="mt-3 grid gap-2"
                                    >
                                        {kompetensi.map((k) => {
                                            const dipilih =
                                                form.data.kompetensi_id ===
                                                String(k.kompetensi_id);

                                            return (
                                                <button
                                                    key={k.kompetensi_id}
                                                    type="button"
                                                    role="radio"
                                                    aria-checked={dipilih}
                                                    onClick={() =>
                                                        form.setData(
                                                            'kompetensi_id',
                                                            String(
                                                                k.kompetensi_id,
                                                            ),
                                                        )
                                                    }
                                                    className={cn(
                                                        'flex items-start gap-3 rounded-2xl border p-4 text-left transition-colors',
                                                        dipilih
                                                            ? 'border-navy-900 bg-navy-50 ring-1 ring-navy-900'
                                                            : 'bg-card hover:bg-navy-50/60',
                                                    )}
                                                >
                                                    <span
                                                        className={cn(
                                                            'mt-0.5 grid size-5 shrink-0 place-items-center rounded-full border-2',
                                                            dipilih
                                                                ? 'border-navy-900'
                                                                : 'border-navy-200',
                                                        )}
                                                    >
                                                        {dipilih && (
                                                            <span className="size-2.5 rounded-full bg-navy-900" />
                                                        )}
                                                    </span>
                                                    <span className="min-w-0 flex-1">
                                                        <span className="block font-bold text-navy-900">
                                                            {
                                                                k.nama_kompetensi_sekolah
                                                            }
                                                        </span>
                                                        <span className="mt-0.5 block text-sm text-muted-foreground">
                                                            {
                                                                k.aktivitas_kompetensi_industri
                                                            }
                                                        </span>
                                                        <span className="mt-2 flex flex-wrap gap-1.5">
                                                            <GapBadge
                                                                warna={k.warna}
                                                                gap={k.gap}
                                                            />
                                                            <StatusBadge
                                                                status={
                                                                    k.status
                                                                }
                                                                label={
                                                                    k.status_label
                                                                }
                                                            />
                                                        </span>
                                                    </span>
                                                    <span className="shrink-0 text-right text-xs text-navy-600">
                                                        Level{' '}
                                                        <strong className="text-base text-navy-900">
                                                            {k.level}
                                                        </strong>
                                                        /{k.target_level}
                                                    </span>
                                                </button>
                                            );
                                        })}
                                    </div>
                                )}
                                {form.errors.kompetensi_id && (
                                    <p className="mt-2 text-sm font-medium text-destructive">
                                        {form.errors.kompetensi_id}
                                    </p>
                                )}
                            </section>

                            <Button
                                type="submit"
                                variant="aksen"
                                size="lg"
                                className="w-full"
                                disabled={
                                    form.processing || kompetensi.length === 0
                                }
                            >
                                {form.processing ? (
                                    <LoaderCircle className="animate-spin" />
                                ) : (
                                    <ArrowRight />
                                )}
                                Mulai Pendampingan Magang
                            </Button>
                        </form>
                    )}
                </main>
            </div>
        </>
    );
}

function Terkunci({
    icon: Icon,
    label,
    children,
}: {
    icon: typeof UserRound;
    label: string;
    children: ReactNode;
}) {
    return (
        <div className="flex items-center gap-3 rounded-2xl border border-dashed bg-navy-50/50 px-3.5 py-2.5">
            <Icon className="size-4 shrink-0 text-navy-400" />
            <div className="min-w-0 flex-1">
                <dt className="text-[11px] font-semibold text-navy-500">
                    {label}
                </dt>
                <dd className="truncate text-sm font-semibold text-navy-900">
                    {children ?? '-'}
                </dd>
            </div>
            <Lock
                className="size-3.5 shrink-0 text-navy-300"
                aria-label="Terkunci"
            />
        </div>
    );
}
