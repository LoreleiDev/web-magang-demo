import type { LucideIcon } from 'lucide-react';
import { LayoutDashboard } from 'lucide-react';
import guru from '@/routes/guru';
import industri from '@/routes/industri';
import siswa from '@/routes/siswa';
import superadmin from '@/routes/superadmin';
import type { Role } from '@/types';

export type NavItem = {
    title: string;
    href: string;
    icon: LucideIcon;
    /** Aktif hanya jika URL sama persis (untuk halaman dashboard/akar). */
    exact?: boolean;
};

/**
 * Menu per role. Item halaman baru ditambahkan di tahap pembangunannya.
 * Siswa: Dashboard · Peta Kompetensi · Learning Gap · Belajar · AI Mentor ·
 * Logbook · Assessment · Progress (CLAUDE.md bagian 6).
 */
export function navigasiUntuk(role: Role): NavItem[] {
    switch (role) {
        case 'superadmin':
            return [
                {
                    title: 'Dashboard',
                    href: superadmin.dashboard().url,
                    icon: LayoutDashboard,
                    exact: true,
                },
            ];
        case 'guru':
            return [
                {
                    title: 'Dashboard',
                    href: guru.dashboard().url,
                    icon: LayoutDashboard,
                    exact: true,
                },
            ];
        case 'industri':
            return [
                {
                    title: 'Dashboard',
                    href: industri.dashboard().url,
                    icon: LayoutDashboard,
                    exact: true,
                },
            ];
        case 'siswa':
            return [
                {
                    title: 'Dashboard',
                    href: siswa.dashboard().url,
                    icon: LayoutDashboard,
                    exact: true,
                },
            ];
    }
}

export function isNavAktif(item: NavItem, urlSekarang: string): boolean {
    const path = urlSekarang.split('?')[0];
    const target = new URL(item.href, 'http://x').pathname;

    return item.exact
        ? path === target
        : path === target || path.startsWith(`${target}/`);
}
