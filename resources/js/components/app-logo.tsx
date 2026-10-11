import { cn } from '@/lib/utils';

/**
 * Logo MagangBridge (buku terbuka, sekolah, jembatan, industri). Ditaruh di kotak
 * putih agar logo navy tetap terlihat di latar gelap (sidebar, halaman login).
 */
export function AppLogoMark({ className }: { className?: string }) {
    return (
        <span
            className={cn(
                'grid size-10 shrink-0 place-items-center rounded-xl bg-white p-1 shadow-sm ring-1 ring-navy-900/5',
                className,
            )}
        >
            <img
                src="/logo-magangbridge.png"
                alt=""
                aria-hidden="true"
                className="size-full object-contain"
            />
        </span>
    );
}

export function AppLogo({
    className,
    tone = 'gelap',
}: {
    className?: string;
    tone?: 'gelap' | 'terang';
}) {
    return (
        <div className={cn('flex items-center gap-2.5', className)}>
            <AppLogoMark />
            <div className="leading-none">
                <div
                    className={cn(
                        'text-[15px] font-extrabold tracking-tight',
                        tone === 'terang' ? 'text-white' : 'text-navy-900',
                    )}
                >
                    MagangBridge
                </div>
                <div
                    className={cn(
                        'mt-1 text-[10px] font-semibold tracking-[0.18em] uppercase',
                        tone === 'terang' ? 'text-navy-300' : 'text-navy-500',
                    )}
                >
                    SMK
                </div>
            </div>
        </div>
    );
}
