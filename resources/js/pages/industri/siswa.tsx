import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, BadgeCheck, Hourglass } from 'lucide-react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import type { DataDetailSiswa } from '@/components/detail-siswa';
import { DetailSiswa } from '@/components/detail-siswa';
import { Button } from '@/components/ui/button';
import industri from '@/routes/industri';
import type { BarisKompetensi } from '@/types/kompetensi';

/**
 * Detail siswa untuk pembimbing industri + Verifikasi Kompetensi (bagian 8 & 11.1).
 */
export default function IndustriSiswa(props: DataDetailSiswa) {
    return (
        <>
            <Head title={props.siswa.nama} />
            <Link
                href={industri.dashboard().url}
                className="mb-4 inline-flex items-center gap-1.5 text-sm font-semibold text-navy-600 hover:text-navy-900"
            >
                <ArrowLeft className="size-4" /> Siswa magang
            </Link>

            <DetailSiswa
                data={props}
                aksiKompetensi={(baris) => (
                    <AksiVerifikasi
                        siswaId={props.siswa.id}
                        siswaNama={props.siswa.nama}
                        baris={baris}
                    />
                )}
            />
        </>
    );
}

function AksiVerifikasi({
    siswaId,
    siswaNama,
    baris,
}: {
    siswaId: number;
    siswaNama: string;
    baris: BarisKompetensi;
}) {
    if (baris.status === 'terverifikasi') {
        return (
            <p className="flex items-center gap-2 text-sm font-semibold text-hijau-700">
                <BadgeCheck className="size-5" />
                Sudah terverifikasi
            </p>
        );
    }

    if (baris.status !== 'menunggu_verifikasi') {
        return (
            <p className="flex items-start gap-2 text-sm text-navy-600">
                <Hourglass className="mt-0.5 size-4 shrink-0 text-navy-400" />
                Bisa diverifikasi setelah level siswa mencapai target{' '}
                {baris.target_level}.
            </p>
        );
    }

    return (
        <ConfirmDialog
            trigger={
                <Button variant="aksen" className="w-full">
                    <BadgeCheck />
                    Verifikasi Kompetensi
                </Button>
            }
            title="Verifikasi kompetensi ini?"
            description={`Anda menyatakan ${siswaNama} sudah menguasai "${baris.nama_kompetensi_sekolah}" sesuai kebutuhan industri. Verifikasi tidak bisa dibatalkan.`}
            confirmLabel="Ya, verifikasi"
            onConfirm={() =>
                router.post(
                    industri.siswa.verifikasi({
                        siswa: siswaId,
                        kompetensi: baris.kompetensi_id,
                    }).url,
                    {},
                    { preserveScroll: true },
                )
            }
        />
    );
}
