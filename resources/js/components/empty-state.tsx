import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

export function EmptyState({
    icon: Icon,
    title,
    description,
    action,
    className,
}: {
    icon: LucideIcon;
    title: string;
    description?: ReactNode;
    action?: ReactNode;
    className?: string;
}) {
    return (
        <div
            className={cn(
                'flex flex-col items-center rounded-3xl border border-dashed bg-card/60 px-6 py-14 text-center',
                className,
            )}
        >
            <span className="grid size-12 place-items-center rounded-2xl bg-navy-50 text-navy-500">
                <Icon className="size-6" />
            </span>
            <h3 className="mt-4 text-base font-bold text-navy-900">{title}</h3>
            {description && (
                <p className="mt-1 max-w-sm text-sm text-muted-foreground">
                    {description}
                </p>
            )}
            {action && <div className="mt-5">{action}</div>}
        </div>
    );
}
