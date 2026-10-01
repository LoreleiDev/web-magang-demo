const tanggal = new Intl.DateTimeFormat('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
});

const tanggalPanjang = new Intl.DateTimeFormat('id-ID', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
});

const tanggalWaktu = new Intl.DateTimeFormat('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
});

/**
 * Tanggal "YYYY-MM-DD" dibaca sebagai tanggal lokal (bukan UTC) agar tidak bergeser sehari.
 */
function keDate(nilai: string): Date {
    return /^\d{4}-\d{2}-\d{2}$/.test(nilai)
        ? new Date(`${nilai}T00:00:00`)
        : new Date(nilai);
}

export function formatTanggal(nilai: string | null | undefined): string {
    return nilai ? tanggal.format(keDate(nilai)) : '-';
}

export function formatTanggalPanjang(nilai: string | null | undefined): string {
    return nilai ? tanggalPanjang.format(keDate(nilai)) : '-';
}

export function formatTanggalWaktu(nilai: string | null | undefined): string {
    return nilai ? tanggalWaktu.format(keDate(nilai)) : '-';
}

export function formatPeriode(mulai: string, selesai: string): string {
    return `${formatTanggal(mulai)} – ${formatTanggal(selesai)}`;
}
