import { Link } from '@inertiajs/react';
import { LayoutGrid } from 'lucide-react';
import { useState } from 'react';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import type { NavItem } from '@/lib/navigation';
import { isNavAktif } from '@/lib/navigation';
import { cn } from '@/lib/utils';

const MAKS_SLOT = 5;

/**
 * Navigasi bawah siswa di smartphone (CLAUDE.md bagian 6).
 * Lebih dari 5 menu: 4 menu pertama + "Lainnya" yang membuka lembar menu.
 * Tinggi tetap h-16 (+ safe area) agar floating chatbot bisa diletakkan di atasnya.
 */
export function BottomNav({ items, url }: { items: NavItem[]; url: string }) {
    const [lainnyaTerbuka, setLainnyaTerbuka] = useState(false);
    const terlaluBanyak = items.length > MAKS_SLOT;
    const utama = terlaluBanyak ? items.slice(0, MAKS_SLOT - 1) : items;
    const lainnya = terlaluBanyak ? items.slice(MAKS_SLOT - 1) : [];
    const lainnyaAktif = lainnya.some((item) => isNavAktif(item, url));

    return (
        <nav
            aria-label="Navigasi utama"
            className="fixed inset-x-0 bottom-0 z-30 border-t bg-card/95 pb-safe shadow-[0_-8px_24px_-16px_rgb(11_37_69/0.25)] backdrop-blur lg:hidden"
        >
            <ul
                className="mx-auto grid h-16 max-w-lg"
                style={{
                    gridTemplateColumns: `repeat(${utama.length + (terlaluBanyak ? 1 : 0)}, minmax(0, 1fr))`,
                }}
            >
                {utama.map((item) => (
                    <li key={item.href}>
                        <BottomNavLink
                            href={item.href}
                            icon={item.icon}
                            title={item.title}
                            aktif={isNavAktif(item, url)}
                        />
                    </li>
                ))}

                {terlaluBanyak && (
                    <li>
                        <Sheet
                            open={lainnyaTerbuka}
                            onOpenChange={setLainnyaTerbuka}
                        >
                            <SheetTrigger asChild>
                                <button
                                    type="button"
                                    className="flex h-full w-full flex-col items-center justify-center gap-1"
                                >
                                    <NavIcon
                                        icon={LayoutGrid}
                                        aktif={lainnyaAktif}
                                    />
                                    <NavLabel aktif={lainnyaAktif}>
                                        Lainnya
                                    </NavLabel>
                                </button>
                            </SheetTrigger>
                            <SheetContent
                                side="bottom"
                                className="rounded-t-3xl pb-safe"
                            >
                                <SheetHeader>
                                    <SheetTitle>Menu lainnya</SheetTitle>
                                </SheetHeader>
                                <div className="grid grid-cols-3 gap-2 px-4 pb-6">
                                    {lainnya.map((item) => (
                                        <Link
                                            key={item.href}
                                            href={item.href}
                                            onClick={() =>
                                                setLainnyaTerbuka(false)
                                            }
                                            className={cn(
                                                'flex flex-col items-center gap-2 rounded-2xl border p-3 text-center text-xs font-semibold',
                                                isNavAktif(item, url)
                                                    ? 'border-navy-900 bg-navy-900 text-white'
                                                    : 'bg-card text-navy-800',
                                            )}
                                        >
                                            <item.icon className="size-5" />
                                            {item.title}
                                        </Link>
                                    ))}
                                </div>
                            </SheetContent>
                        </Sheet>
                    </li>
                )}
            </ul>
        </nav>
    );
}

function BottomNavLink({
    href,
    icon,
    title,
    aktif,
}: {
    href: string;
    icon: NavItem['icon'];
    title: string;
    aktif: boolean;
}) {
    return (
        <Link
            href={href}
            aria-current={aktif ? 'page' : undefined}
            className="flex h-full flex-col items-center justify-center gap-1"
        >
            <NavIcon icon={icon} aktif={aktif} />
            <NavLabel aktif={aktif}>{title}</NavLabel>
        </Link>
    );
}

function NavIcon({
    icon: Icon,
    aktif,
}: {
    icon: NavItem['icon'];
    aktif: boolean;
}) {
    return (
        <span
            className={cn(
                'grid h-7 w-12 place-items-center rounded-full transition-colors',
                aktif ? 'bg-navy-900 text-white' : 'text-navy-400',
            )}
        >
            <Icon className="size-[18px]" />
        </span>
    );
}

function NavLabel({
    aktif,
    children,
}: {
    aktif: boolean;
    children: React.ReactNode;
}) {
    return (
        <span
            className={cn(
                'max-w-full truncate px-1 text-[11px] leading-none',
                aktif ? 'font-bold text-navy-900' : 'font-medium text-navy-500',
            )}
        >
            {children}
        </span>
    );
}
