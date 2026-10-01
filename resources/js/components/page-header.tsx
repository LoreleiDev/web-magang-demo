import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

export function PageHeader({
    eyebrow,
    title,
    description,
    actions,
    className,
}: {
    eyebrow?: string;
    title: string;
    description?: ReactNode;
    actions?: ReactNode;
    className?: string;
}) {
    return (
        <header
            className={cn(
                'mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between',
                className,
            )}
        >
            <div className="min-w-0">
                {eyebrow && (
                    <p className="text-xs font-bold tracking-[0.16em] text-hijau-600 uppercase">
                        {eyebrow}
                    </p>
                )}
                <h1 className="mt-1.5 text-2xl font-extrabold tracking-tight text-balance text-navy-900 sm:text-3xl">
                    {title}
                </h1>
                {description && (
                    <p className="mt-2 max-w-2xl text-sm text-muted-foreground sm:text-base">
                        {description}
                    </p>
                )}
            </div>
            {actions && <div className="flex shrink-0 gap-2">{actions}</div>}
        </header>
    );
}
