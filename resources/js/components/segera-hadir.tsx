import type { LucideIcon } from 'lucide-react';
import { Hammer } from 'lucide-react';

/**
 * Penanda sementara untuk halaman yang dibangun di tahap berikutnya.
 */
export function SegeraHadir({
    tahap,
    daftar,
    icon: Icon = Hammer,
}: {
    tahap: string;
    daftar: string[];
    icon?: LucideIcon;
}) {
    return (
        <section className="rounded-3xl border bg-card p-2 shadow-sm">
            <div className="rounded-[20px] bg-navy-50/70 p-5 sm:p-7">
                <div className="flex items-start gap-4">
                    <span className="grid size-11 shrink-0 place-items-center rounded-xl bg-navy-900 text-white shadow-md">
                        <Icon className="size-5" />
                    </span>
                    <div>
                        <h2 className="text-base font-bold text-navy-900">
                            Fitur halaman ini dibangun di {tahap}
                        </h2>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Yang akan tersedia:
                        </p>
                    </div>
                </div>
                <ul className="mt-5 grid gap-2 sm:grid-cols-2">
                    {daftar.map((item) => (
                        <li
                            key={item}
                            className="flex items-center gap-3 rounded-xl border bg-card px-4 py-3 text-sm font-medium text-navy-800"
                        >
                            <span className="size-1.5 shrink-0 rounded-full bg-hijau-500" />
                            {item}
                        </li>
                    ))}
                </ul>
            </div>
        </section>
    );
}
