import type { LucideIcon } from 'lucide-react';
import {
    BookOpen,
    Building2,
    ChartColumn,
    Map as MapIcon,
    ClipboardCheck,
    FileText,
    GraduationCap,
    Target,
    LayoutDashboard,
    NotebookPen,
    Users,
    UsersRound,
} from 'lucide-react';
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
                {
                    title: 'Akun',
                    href: superadmin.akun.index().url,
                    icon: Users,
                },
                {
                    title: 'Perusahaan',
                    href: superadmin.perusahaan.index().url,
                    icon: Building2,
                },
                {
                    title: 'Kelompok Magang',
                    href: superadmin.kelompok.index().url,
                    icon: UsersRound,
                },
                {
                    title: 'Program Keahlian',
                    href: superadmin.programKeahlian.index().url,
                    icon: GraduationCap,
                },
                {
                    title: 'Materi',
                    href: superadmin.materi.index().url,
                    icon: BookOpen,
                },
                {
                    title: 'Logbook',
                    href: superadmin.logbook.index().url,
                    icon: NotebookPen,
                },
                {
                    title: 'Assessment',
                    href: superadmin.assessment.index().url,
                    icon: ClipboardCheck,
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
                {
                    title: 'Kompetensi',
                    href: guru.kompetensi.index().url,
                    icon: Target,
                },
                {
                    title: 'Materi',
                    href: guru.materi.index().url,
                    icon: BookOpen,
                },
                {
                    title: 'Dokumen Sekolah',
                    href: guru.dokumen.index().url,
                    icon: FileText,
                },
            ];
        case 'industri':
            return [
                {
                    title: 'Siswa Magang',
                    href: industri.dashboard().url,
                    icon: LayoutDashboard,
                    exact: true,
                },
                {
                    title: 'Verifikasi Materi',
                    href: industri.materi.index().url,
                    icon: BookOpen,
                },
            ];
        // Urutan menentukan bottom nav: 4 menu pertama tampil, sisanya di "Lainnya".
        // AI Mentor ditambahkan sebagai chatbot mengambang di Tahap 6.
        case 'siswa':
            return [
                {
                    title: 'Dashboard',
                    href: siswa.dashboard().url,
                    icon: LayoutDashboard,
                    exact: true,
                },
                {
                    title: 'Belajar',
                    href: siswa.belajar.index().url,
                    icon: BookOpen,
                },
                {
                    title: 'Logbook',
                    href: siswa.logbook.index().url,
                    icon: NotebookPen,
                },
                {
                    title: 'Learning Gap',
                    href: siswa.gap().url,
                    icon: Target,
                },
                {
                    title: 'Peta Kompetensi',
                    href: siswa.peta().url,
                    icon: MapIcon,
                },
                {
                    title: 'Assessment',
                    href: siswa.assessment().url,
                    icon: ClipboardCheck,
                },
                {
                    title: 'Progress',
                    href: siswa.progress().url,
                    icon: ChartColumn,
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
