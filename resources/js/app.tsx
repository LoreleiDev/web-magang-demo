import { createInertiaApp } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';

const appName = import.meta.env.VITE_APP_NAME || 'MagangBridge SMK';

void createInertiaApp({
    title: (title) => (title ? `${title} · ${appName}` : appName),
    // Login, Mulai Pendampingan, dan halaman error tampil tanpa sidebar/navigasi.
    layout: (name) =>
        name.startsWith('auth/') || name === 'siswa/mulai' || name === 'error'
            ? null
            : AppLayout,
    progress: {
        color: '#12a170',
    },
});
