import { Head, usePage } from '@inertiajs/react';
import { PageHeader } from '@/components/page-header';
import { SegeraHadir } from '@/components/segera-hadir';

export default function GuruDashboard() {
    const { auth } = usePage().props;

    return (
        <>
            <Head title="Dashboard Guru" />
            <PageHeader
                eyebrow="Dashboard Guru Pembimbing"
                title={`Halo, ${auth.user?.name}`}
                description="Pantau siswa di kelompok magang yang Anda bimbing."
            />
            <SegeraHadir
                tahap="Tahap 3"
                daftar={[
                    'Daftar siswa per kelompok magang',
                    'Isi level kompetensi siswa',
                    'Manajemen kompetensi',
                    'Manajemen materi dan kuis',
                    'Dokumen sekolah untuk AI Mentor',
                ]}
            />
        </>
    );
}
