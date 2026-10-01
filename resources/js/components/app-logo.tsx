import { cn } from '@/lib/utils';

/**
 * Tanda MagangBridge: lengkung jembatan (sekolah ↔ industri) dengan titik hijau di puncaknya.
 */
export function AppLogoMark({
    className,
    tone = 'gelap',
}: {
    className?: string;
    tone?: 'gelap' | 'terang';
}) {
    return (
        <svg
            viewBox="0 0 32 32"
            aria-hidden="true"
            className={cn('size-9 shrink-0', className)}
        >
            <rect
                width="32"
                height="32"
                rx="9"
                className={
                    tone === 'terang' ? 'fill-navy-700' : 'fill-navy-900'
                }
            />
            <path
                d="M6 21.5h20"
                className="stroke-white"
                strokeWidth="2.2"
                strokeLinecap="round"
            />
            <path
                d="M8 21.5C9.5 15.5 12.5 12.5 16 12.5s6.5 3 8 9"
                fill="none"
                className="stroke-navy-200"
                strokeWidth="2.2"
                strokeLinecap="round"
            />
            <path
                d="M12 21.5v-4.6M20 21.5v-4.6"
                className="stroke-navy-300"
                strokeWidth="1.6"
                strokeLinecap="round"
            />
            <circle cx="16" cy="12.5" r="2.6" className="fill-hijau-500" />
        </svg>
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
            <AppLogoMark tone={tone} />
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
