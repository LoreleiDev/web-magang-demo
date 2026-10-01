import { Head, usePage } from '@inertiajs/react';
import { PageHeader } from '@/components/page-header';
import { SegeraHadir } from '@/components/segera-hadir';

export default function SiswaDashboard() {
    const { auth } = usePage().props;

    return (
        <>
            <Head title="Dashboard" />
            <PageHeader
                eyebrow="Pendampingan Magang"
                title={`Selamat datang, ${auth.user?.name}`}
            />
            <SegeraHadir
                tahap="Tahap 4"
                daftar={[
                    'Mulai Pendampingan',
                    'Dashboard dan Progress',
                    'Peta Kompetensi dan Learning Gap',
                    'Belajar (8 langkah) dan Assessment',
                    'Logbook harian',
                ]}
            />
        </>
    );
}
