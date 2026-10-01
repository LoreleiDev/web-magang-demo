import type { ReactNode } from 'react';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

/**
 * Label + isian + petunjuk + pesan error dalam satu blok.
 */
export function FormField({
    label,
    htmlFor,
    error,
    hint,
    wajib,
    className,
    children,
}: {
    label: string;
    htmlFor?: string;
    error?: string;
    hint?: ReactNode;
    wajib?: boolean;
    className?: string;
    children: ReactNode;
}) {
    return (
        <div className={cn('space-y-2', className)}>
            <Label htmlFor={htmlFor} className="text-navy-900">
                {label}
                {wajib && (
                    <span className="text-gap-tinggi" aria-hidden="true">
                        *
                    </span>
                )}
            </Label>
            {children}
            {hint && !error && (
                <p className="text-xs leading-relaxed text-muted-foreground">
                    {hint}
                </p>
            )}
            {error && (
                <p className="text-sm font-medium text-destructive">{error}</p>
            )}
        </div>
    );
}

/**
 * Bagian form berjudul. Di layar lebar judul di kiri, isian di kanan.
 */
export function FormSection({
    title,
    description,
    children,
}: {
    title: string;
    description?: ReactNode;
    children: ReactNode;
}) {
    return (
        <section className="grid gap-5 border-b border-dashed py-8 first:pt-0 last:border-none last:pb-0 lg:grid-cols-[16rem_1fr] lg:gap-10">
            <div>
                <h2 className="text-base font-bold text-navy-900">{title}</h2>
                {description && (
                    <p className="mt-1 text-sm text-muted-foreground">
                        {description}
                    </p>
                )}
            </div>
            <div className="min-w-0 space-y-5">{children}</div>
        </section>
    );
}
