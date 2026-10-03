import { Link, usePage } from '@inertiajs/react';
import { LogOut, Menu } from 'lucide-react';
import type { ReactNode } from 'react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';
import { AiMentorChatbot } from '@/components/ai-mentor/chatbot';
import { AppLogo } from '@/components/app-logo';
import { BottomNav } from '@/components/bottom-nav';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { Toaster } from '@/components/ui/sonner';
import { TooltipProvider } from '@/components/ui/tooltip';
import { UserAvatar, UserMenu } from '@/components/user-menu';
import type { NavItem } from '@/lib/navigation';
import { isNavAktif, navigasiUntuk } from '@/lib/navigation';
import { cn } from '@/lib/utils';
import { konfirmasiKeluar } from '@/lib/konfirmasi-keluar';
import { home } from '@/routes';
import type { User } from '@/types';

/**
 * Kerangka semua halaman setelah login.
 * - Desktop (lg+): sidebar biru tua di kiri.
 * - Smartphone/tablet: header atas; siswa memakai bottom navigation,
 *   role lain membuka menu lewat tombol di header.
 */
export default function AppLayout({ children }: { children: ReactNode }) {
    const page = usePage();
    const user = page.props.auth.user;
    const flash = page.flash;

    useEffect(() => {
        if (flash.toast) {
            toast[flash.toast.type](flash.toast.message);
        }
    }, [flash]);

    if (!user) {
        return <>{children}</>;
    }

    const nav = navigasiUntuk(user.role);
    const pakaiBottomNav = user.role === 'siswa';

    return (
        <TooltipProvider delayDuration={200}>
            <div className="min-h-dvh bg-background">
                <aside className="fixed inset-y-0 left-0 z-30 hidden w-68 flex-col bg-navy-900 tekstur-titik lg:flex">
                    <SidebarIsi nav={nav} user={user} url={page.url} />
                </aside>

                <header className="sticky top-0 z-20 flex h-14 items-center gap-2 border-b bg-card/95 px-4 backdrop-blur sm:px-6 lg:hidden">
                    {!pakaiBottomNav && (
                        <MenuSeluler nav={nav} user={user} url={page.url} />
                    )}
                    <Link href={home()} className="mr-auto">
                        <AppLogo />
                    </Link>
                    <UserMenu user={user} />
                </header>

                <main
                    className={cn(
                        'lg:pl-68',
                        // Ruang untuk bottom nav + tombol AI Mentor agar konten
                        // terakhir tidak tertutup.
                        pakaiBottomNav &&
                            'pb-[calc(8rem+env(safe-area-inset-bottom))] lg:pb-16',
                    )}
                >
                    <div className="mx-auto w-full max-w-6xl px-4 py-6 sm:px-6 lg:px-10 lg:py-10">
                        {children}
                    </div>
                </main>

                {pakaiBottomNav && <BottomNav items={nav} url={page.url} />}

                {/* AI Mentor hanya untuk siswa (bagian 2.1 & 6.5). */}
                {user.role === 'siswa' && <AiMentorChatbot />}

                <Toaster position="top-center" richColors closeButton />
            </div>
        </TooltipProvider>
    );
}

function SidebarIsi({
    nav,
    user,
    url,
    onNavigate,
}: {
    nav: NavItem[];
    user: User;
    url: string;
    onNavigate?: () => void;
}) {
    return (
        <div className="flex h-full flex-col">
            <div className="px-6 pt-7 pb-8">
                <Link href={home()} onClick={onNavigate}>
                    <AppLogo tone="terang" />
                </Link>
            </div>

            <nav
                aria-label="Navigasi utama"
                className="flex-1 overflow-y-auto px-3"
            >
                <ul className="space-y-1">
                    {nav.map((item) => {
                        const aktif = isNavAktif(item, url);

                        return (
                            <li key={item.href}>
                                <Link
                                    href={item.href}
                                    onClick={onNavigate}
                                    aria-current={aktif ? 'page' : undefined}
                                    className={cn(
                                        'relative flex h-11 items-center gap-3 rounded-xl px-3 text-sm font-semibold transition-colors',
                                        aktif
                                            ? 'bg-white/10 text-white'
                                            : 'text-navy-200 hover:bg-white/5 hover:text-white',
                                    )}
                                >
                                    {aktif && (
                                        <span className="absolute top-2.5 bottom-2.5 left-0 w-1 rounded-full bg-hijau-500" />
                                    )}
                                    <item.icon className="size-[18px]" />
                                    {item.title}
                                </Link>
                            </li>
                        );
                    })}
                </ul>
            </nav>

            <div className="m-3 rounded-2xl border border-white/10 bg-white/5 p-3">
                <div className="flex items-center gap-3">
                    <UserAvatar
                        user={user}
                        className="bg-navy-700 text-navy-50"
                    />
                    <div className="min-w-0 flex-1">
                        <div className="truncate text-sm font-semibold text-white">
                            {user.name}
                        </div>
                        <div className="truncate text-xs text-navy-300">
                            {user.role_label}
                        </div>
                    </div>
                </div>
                <button
                    type="button"
                    onClick={() => {
                        onNavigate?.();
                        void konfirmasiKeluar();
                    }}
                    className="mt-3 flex h-9 w-full items-center justify-center gap-2 rounded-lg text-xs font-semibold text-navy-200 transition-colors hover:bg-white/10 hover:text-white"
                >
                    <LogOut className="size-4" />
                    Keluar
                </button>
            </div>
        </div>
    );
}

function MenuSeluler({
    nav,
    user,
    url,
}: {
    nav: NavItem[];
    user: User;
    url: string;
}) {
    const [terbuka, setTerbuka] = useState(false);

    return (
        <Sheet open={terbuka} onOpenChange={setTerbuka}>
            <SheetTrigger asChild>
                <Button
                    variant="ghost"
                    size="icon"
                    className="-ml-2"
                    aria-label="Buka menu"
                >
                    <Menu className="size-5" />
                </Button>
            </SheetTrigger>
            <SheetContent
                side="left"
                className="w-72 border-none bg-navy-900 tekstur-titik p-0 text-white [&>button]:text-navy-200"
            >
                <SheetTitle className="sr-only">Menu</SheetTitle>
                <SheetDescription className="sr-only">
                    Navigasi utama aplikasi
                </SheetDescription>
                <SidebarIsi
                    nav={nav}
                    user={user}
                    url={url}
                    onNavigate={() => setTerbuka(false)}
                />
            </SheetContent>
        </Sheet>
    );
}
