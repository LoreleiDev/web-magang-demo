import { HalamanLogin } from '@/components/halaman-login';
import type { Role } from '@/types';

const eyebrow: Record<Role, string> = {
    siswa: 'Login siswa PKL',
    guru: 'Login guru pembimbing',
    industri: 'Login pembimbing industri',
    superadmin: 'Login admin sekolah',
};

/**
 * Satu halaman untuk keempat URL login (keputusan 13 no. 32). Server menentukan
 * role (`portal`) dan alamat kirimnya.
 */
export default function Login({
    portal,
    judul,
    aksi,
}: {
    portal: Role;
    judul: string;
    aksi: string;
}) {
    return (
        <HalamanLogin
            judulTab={judul}
            eyebrow={eyebrow[portal]}
            judul={judul}
            deskripsi="Gunakan email dan kata sandi yang diberikan sekolah."
            aksi={aksi}
        />
    );
}
