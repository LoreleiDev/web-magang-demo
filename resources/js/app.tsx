import { createInertiaApp } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';

const appName = import.meta.env.VITE_APP_NAME || 'MagangBridge SMK';

void createInertiaApp({
    title: (title) => (title ? `${title} · ${appName}` : appName),
    // Halaman di folder auth/ (login) tampil tanpa sidebar/navigasi.
    layout: (name) => (name.startsWith('auth/') ? null : AppLayout),
    progress: {
        color: '#12a170',
    },
});
