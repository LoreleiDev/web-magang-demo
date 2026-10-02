import { Form, Head } from '@inertiajs/react';
import {
    BookOpenCheck,
    Eye,
    EyeOff,
    LoaderCircle,
    Map,
    MessagesSquare,
} from 'lucide-react';
import { useState } from 'react';
import { AppLogo } from '@/components/app-logo';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const sorotan = [
    {
        icon: Map,
        judul: 'Peta kompetensi',
        isi: 'Lihat jarak antara pelajaran sekolah dan pekerjaan di industri.',
    },
    {
        icon: BookOpenCheck,
        judul: 'Belajar singkat',
        isi: 'Materi 8 langkah yang bisa dibuka di sela waktu magang.',
    },
    {
        icon: MessagesSquare,
        judul: 'AI Mentor Magang',
        isi: 'Teman bertanya saat menemui hal baru di tempat kerja.',
    },
];

/**
 * Tampilan login bersama untuk semua halaman login per role (keputusan 13 no. 32).
 * Sengaja tidak ada tautan ke halaman login role lain.
 */
export function HalamanLogin({
    judulTab,
    eyebrow,
    judul,
    deskripsi,
    aksi,
}: {
    judulTab: string;
    eyebrow: string;
    judul: string;
    deskripsi: string;
    aksi: string;
}) {
    const [lihatSandi, setLihatSandi] = useState(false);

    return (
        <>
            <Head title={judulTab} />

            <div className="grid min-h-dvh bg-background lg:grid-cols-[1.05fr_1fr]">
                {/* Panel identitas: pita atas di smartphone, kolom kiri di desktop. */}
                <section className="relative overflow-hidden bg-navy-900 tekstur-titik px-6 pt-8 pb-24 text-white sm:px-10 lg:flex lg:flex-col lg:justify-between lg:px-14 lg:py-14">
                    <AppLogo tone="terang" />

                    <div className="mt-10 max-w-md lg:mt-0">
                        <p className="text-xs font-bold tracking-[0.2em] text-hijau-500 uppercase">
                            {eyebrow}
                        </p>
                        <h1 className="mt-3 text-[28px] leading-[1.15] font-extrabold tracking-tight text-balance sm:text-4xl lg:text-[44px]">
                            Hubungkan yang dipelajari di sekolah dengan yang
                            dikerjakan di industri.
                        </h1>
                    </div>

                    <ul className="mt-12 hidden max-w-md space-y-5 lg:block">
                        {sorotan.map((item) => (
                            <li key={item.judul} className="flex gap-4">
                                <span className="grid size-10 shrink-0 place-items-center rounded-xl border border-white/10 bg-white/5 text-navy-200">
                                    <item.icon className="size-5" />
                                </span>
                                <div>
                                    <div className="text-sm font-bold">
                                        {item.judul}
                                    </div>
                                    <div className="mt-0.5 text-sm text-navy-300">
                                        {item.isi}
                                    </div>
                                </div>
                            </li>
                        ))}
                    </ul>
                </section>

                <section className="-mt-16 px-4 pb-10 sm:px-10 lg:mt-0 lg:flex lg:items-center lg:justify-center lg:py-14">
                    <div className="relative mx-auto w-full max-w-md rounded-3xl border bg-card p-6 shadow-lg sm:p-8 lg:border-none lg:bg-transparent lg:p-0 lg:shadow-none">
                        <h2 className="text-2xl font-extrabold tracking-tight text-navy-900">
                            {judul}
                        </h2>
                        <p className="mt-1.5 text-sm text-muted-foreground">
                            {deskripsi}
                        </p>

                        <Form
                            action={aksi}
                            method="post"
                            resetOnError={['password']}
                            className="mt-8 space-y-5"
                        >
                            {({ errors, processing }) => (
                                <>
                                    <div className="space-y-2">
                                        <Label htmlFor="email">Email</Label>
                                        <Input
                                            id="email"
                                            name="email"
                                            type="email"
                                            autoComplete="email"
                                            inputMode="email"
                                            autoFocus
                                            required
                                            placeholder="nama@sekolah.sch.id"
                                            aria-invalid={!!errors.email}
                                            className="h-12 rounded-xl"
                                        />
                                        {errors.email && (
                                            <p className="text-sm font-medium text-destructive">
                                                {errors.email}
                                            </p>
                                        )}
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="password">
                                            Kata sandi
                                        </Label>
                                        <div className="relative">
                                            <Input
                                                id="password"
                                                name="password"
                                                type={
                                                    lihatSandi
                                                        ? 'text'
                                                        : 'password'
                                                }
                                                autoComplete="current-password"
                                                required
                                                aria-invalid={!!errors.password}
                                                className="h-12 rounded-xl pr-12"
                                            />
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    setLihatSandi((v) => !v)
                                                }
                                                aria-label={
                                                    lihatSandi
                                                        ? 'Sembunyikan kata sandi'
                                                        : 'Tampilkan kata sandi'
                                                }
                                                className="absolute inset-y-1 right-1 grid w-10 place-items-center rounded-lg text-muted-foreground hover:text-navy-900"
                                            >
                                                {lihatSandi ? (
                                                    <EyeOff className="size-[18px]" />
                                                ) : (
                                                    <Eye className="size-[18px]" />
                                                )}
                                            </button>
                                        </div>
                                        {errors.password && (
                                            <p className="text-sm font-medium text-destructive">
                                                {errors.password}
                                            </p>
                                        )}
                                    </div>

                                    <label className="flex w-fit items-center gap-2.5 text-sm font-medium text-navy-800">
                                        <Checkbox name="remember" value="1" />
                                        Ingat saya di perangkat ini
                                    </label>

                                    <Button
                                        type="submit"
                                        size="lg"
                                        className="w-full"
                                        disabled={processing}
                                    >
                                        {processing && (
                                            <LoaderCircle className="animate-spin" />
                                        )}
                                        Masuk
                                    </Button>
                                </>
                            )}
                        </Form>

                        <p className="mt-8 rounded-xl bg-navy-50 px-4 py-3 text-xs leading-relaxed text-navy-700">
                            Akun dibuat oleh admin sekolah. Jika belum punya
                            akun atau lupa kata sandi, hubungi guru pembimbing
                            Anda.
                        </p>
                    </div>
                </section>
            </div>
        </>
    );
}
