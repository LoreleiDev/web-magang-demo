import { router } from '@inertiajs/react';
import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';
import { logout } from '@/routes';

/**
 * Konfirmasi sebelum keluar (keputusan 13 no. 33). Tampilan SweetAlert2 diganti
 * dengan kelas tema (font, warna biru tua, radius) agar serasi dengan dialog lain.
 */
export async function konfirmasiKeluar(): Promise<void> {
    const hasil = await Swal.fire({
        title: 'Keluar dari akun?',
        text: 'Anda perlu memasukkan email dan kata sandi lagi untuk masuk.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, keluar',
        cancelButtonText: 'Batal',
        reverseButtons: true,
        focusCancel: true,
        buttonsStyling: false,
        customClass: {
            popup: '!rounded-3xl !font-sans !p-6 !shadow-lg',
            title: '!text-xl !font-extrabold !text-navy-900',
            htmlContainer: '!text-sm !text-muted-foreground',
            icon: '!border-navy-200 !text-navy-700',
            actions: '!gap-2 !mt-6',
            confirmButton:
                'h-10 rounded-lg bg-navy-900 px-5 text-sm font-semibold text-white hover:bg-navy-800',
            cancelButton:
                'h-10 rounded-lg border bg-card px-5 text-sm font-semibold text-navy-800 hover:bg-navy-50',
        },
    });

    if (hasil.isConfirmed) {
        router.post(logout().url);
    }
}
