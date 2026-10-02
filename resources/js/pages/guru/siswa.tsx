import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import type { DataDetailSiswa } from '@/components/detail-siswa';
import { DetailSiswa } from '@/components/detail-siswa';
import guru from '@/routes/guru';

/**
 * Detail siswa untuk guru. Level siswa naik otomatis dari kuis (bagian 11.1).
 */
export default function GuruSiswa(props: DataDetailSiswa) {
    return (
        <>
            <Head title={props.siswa.nama} />
            <Link
                href={guru.dashboard().url}
                className="mb-4 inline-flex items-center gap-1.5 text-sm font-semibold text-navy-600 hover:text-navy-900"
            >
                <ArrowLeft className="size-4" /> Daftar siswa
            </Link>

            <DetailSiswa data={props} />
        </>
    );
}
