import { Link } from '@inertiajs/react';
import { LogOut } from 'lucide-react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { cn, inisial } from '@/lib/utils';
import { logout } from '@/routes';
import type { User } from '@/types';

export function UserAvatar({
    user,
    className,
}: {
    user: User;
    className?: string;
}) {
    return (
        <span
            className={cn(
                'grid size-9 shrink-0 place-items-center rounded-full bg-navy-100 text-xs font-bold text-navy-800',
                className,
            )}
        >
            {inisial(user.name)}
        </span>
    );
}

/**
 * Menu akun di header smartphone/tablet: identitas pengguna + tombol keluar.
 */
export function UserMenu({ user }: { user: User }) {
    return (
        <DropdownMenu>
            <DropdownMenuTrigger
                className="rounded-full outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50"
                aria-label="Menu akun"
            >
                <UserAvatar user={user} />
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-60 rounded-xl p-1.5">
                <DropdownMenuLabel className="px-2.5 py-2">
                    <div className="truncate text-sm font-semibold text-foreground">
                        {user.name}
                    </div>
                    <div className="truncate text-xs font-normal text-muted-foreground">
                        {user.email}
                    </div>
                    <div className="mt-2 inline-flex rounded-full bg-navy-50 px-2 py-0.5 text-[11px] font-semibold text-navy-700">
                        {user.role_label}
                    </div>
                </DropdownMenuLabel>
                <DropdownMenuSeparator />
                <DropdownMenuItem asChild className="rounded-lg px-2.5 py-2">
                    <Link href={logout()} as="button" className="w-full">
                        <LogOut />
                        Keluar
                    </Link>
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
