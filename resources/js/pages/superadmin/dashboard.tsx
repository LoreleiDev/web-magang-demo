import { Head, usePage } from '@inertiajs/react';
import { PageHeader } from '@/components/page-header';
import { SegeraHadir } from '@/components/segera-hadir';

export default function SuperadminDashboard() {
    const { auth } = usePage().props;

    return (
        <>
            <Head title="Panel Superadmin" />
            <PageHeader
                eyebrow="Panel Superadmin"
                title={`Halo, ${auth.user?.name}`}
                description="Kelola akun, data perusahaan, dan kelompok magang."
            />
            <SegeraHadir
                tahap="Tahap 2"
                daftar={[
                    'Manajemen akun guru, siswa, dan industri',
                    'Data perusahaan dan unit kerja',
                    'Dokumen industri untuk AI Mentor',
                    'Kelompok magang',
                ]}
            />
        </>
    );
}
