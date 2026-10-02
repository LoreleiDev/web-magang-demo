import { HalamanLogin } from '@/components/halaman-login';
import { login } from '@/routes';
import { store } from '@/routes/siswa/login';

/**
 * Login khusus siswa (keputusan 13 no. 18). Setelah masuk, siswa memilih
 * kompetensi yang dijalani di halaman Mulai Pendampingan.
 */
export default function LoginSiswa() {
    return (
        <HalamanLogin
            judulTab="Login Siswa"
            eyebrow="Login siswa PKL"
            judul="Masuk sebagai siswa"
            deskripsi="Setelah masuk, pilih kompetensi yang ingin Anda jalani hari ini."
            aksi={store.form()}
            tautanLain={{
                href: login().url,
                teks: 'Guru, industri, atau admin?',
                label: 'Masuk di sini',
            }}
        />
    );
}
