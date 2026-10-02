import { HalamanLogin } from '@/components/halaman-login';
import { store } from '@/routes/login';
import siswa from '@/routes/siswa';

/**
 * Login guru, pembimbing industri, dan admin sekolah.
 */
export default function Login() {
    return (
        <HalamanLogin
            judulTab="Masuk"
            eyebrow="Guru · Industri · Admin sekolah"
            judul="Masuk ke akun Anda"
            deskripsi="Gunakan email dan kata sandi yang diberikan sekolah."
            aksi={store.form()}
            tautanLain={{
                href: siswa.login().url,
                teks: 'Anda siswa?',
                label: 'Masuk di Login Siswa',
            }}
        />
    );
}
