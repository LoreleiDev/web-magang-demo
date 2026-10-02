/**
 * Permintaan JSON ke backend Laravel (bukan kunjungan halaman Inertia).
 * Token CSRF diambil dari cookie XSRF-TOKEN yang disetel Laravel.
 */
export class GagalHttp extends Error {
    constructor(
        message: string,
        public status: number,
    ) {
        super(message);
    }
}

function tokenXsrf(): string {
    const cocok = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);

    return cocok ? decodeURIComponent(cocok[1]) : '';
}

export async function mintaJson<T>(
    method: 'GET' | 'POST' | 'DELETE',
    url: string,
    body?: unknown,
): Promise<T> {
    let respons: Response;

    try {
        respons = await fetch(url, {
            method,
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': tokenXsrf(),
            },
            ...(body === undefined ? {} : { body: JSON.stringify(body) }),
        });
    } catch {
        throw new GagalHttp(
            'Tidak bisa terhubung. Periksa koneksi internet Anda.',
            0,
        );
    }

    const data = (await respons.json().catch(() => ({}))) as {
        message?: string;
        errors?: Record<string, string[]>;
    };

    if (!respons.ok) {
        const pesanValidasi = data.errors
            ? Object.values(data.errors)[0]?.[0]
            : undefined;

        throw new GagalHttp(
            pesanValidasi ??
                data.message ??
                'Terjadi kesalahan. Silakan coba lagi.',
            respons.status,
        );
    }

    return data as T;
}
