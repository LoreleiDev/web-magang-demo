import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, House } from 'lucide-react';
import { AppLogo } from '@/components/app-logo';
import { Button } from '@/components/ui/button';
import { home } from '@/routes';

const pesan: Record<number, { judul: string; isi: string }> = {
    403: {
        judul: 'Anda tidak punya akses ke halaman ini',
        isi: 'Halaman ini hanya untuk peran pengguna tertentu. Jika menurut Anda ini keliru, hubungi admin sekolah.',
    },
    404: {
        judul: 'Halaman tidak ditemukan',
        isi: 'Alamat yang dibuka mungkin salah ketik, atau datanya sudah dihapus.',
    },
    429: {
        judul: 'Terlalu banyak permintaan',
        isi: 'Tunggu sekitar satu menit, lalu coba lagi.',
    },
    500: {
        judul: 'Terjadi kesalahan di server',
        isi: 'Maaf, ada masalah saat memproses permintaan Anda. Coba lagi beberapa saat lagi.',
    },
    503: {
        judul: 'Website sedang dalam perbaikan',
        isi: 'Kami sedang melakukan pemeliharaan. Silakan kembali beberapa saat lagi.',
    },
};

/**
 * Halaman error berbahasa Indonesia (403, 404, 429, 500, 503).
 */
export default function HalamanError({ status }: { status: number }) {
    const { judul, isi } = pesan[status] ?? pesan[500];

    return (
        <>
            <Head title={judul} />
            <main className="grid min-h-svh place-items-center bg-navy-950 tekstur-titik px-4 py-10">
                <div className="w-full max-w-md rounded-3xl bg-card p-6 text-center shadow-xl sm:p-8">
                    <AppLogo className="justify-center" />
                    <p className="mt-8 text-6xl font-extrabold tracking-tight text-navy-900 tabular-nums">
                        {status}
                    </p>
                    <h1 className="mt-3 text-xl font-bold text-navy-900">
                        {judul}
                    </h1>
                    <p className="mt-2 text-sm text-muted-foreground">{isi}</p>
                    <div className="mt-8 flex flex-col gap-2 sm:flex-row sm:justify-center">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => window.history.back()}
                        >
                            <ArrowLeft />
                            Kembali
                        </Button>
                        <Button asChild>
                            <Link href={home().url}>
                                <House />
                                Ke halaman utama
                            </Link>
                        </Button>
                    </div>
                </div>
            </main>
        </>
    );
}
