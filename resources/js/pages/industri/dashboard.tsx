import { Head, usePage } from '@inertiajs/react';
import { PageHeader } from '@/components/page-header';
import { SegeraHadir } from '@/components/segera-hadir';

export default function IndustriDashboard() {
    const { auth } = usePage().props;

    return (
        <>
            <Head title="Dashboard Industri" />
            <PageHeader
                eyebrow="Dashboard Pembimbing Industri"
                title={`Halo, ${auth.user?.name}`}
                description="Pantau siswa yang magang di perusahaan Anda."
            />
            <SegeraHadir
                tahap="Tahap 5"
                daftar={[
                    'Daftar siswa magang di perusahaan Anda',
                    'Verifikasi kompetensi siswa',
                    'Verifikasi materi dan masukan untuk guru',
                ]}
            />
        </>
    );
}
