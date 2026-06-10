import { Link, usePage } from '@inertiajs/react';
import { LogOut } from 'lucide-react';
import { getSecondaryNav } from '@/config/adminNav';
import { Sheet, SheetContent, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import type { PageProps } from '@/types';
import { cn } from '@/lib/utils';

interface Props {
    open: boolean;
    onOpenChange: (open: boolean) => void;
}

export function AdminMobileMenu({ open, onOpenChange }: Props) {
    const { url, props } = usePage<PageProps>();
    const user = props.auth.user;
    const items = getSecondaryNav(user?.role);

    return (
        <Sheet open={open} onOpenChange={onOpenChange}>
            <SheetContent side="bottom" className="max-h-[85vh] overflow-y-auto rounded-t-xl pb-[env(safe-area-inset-bottom,0px)]">
                <SheetHeader>
                    <SheetTitle>Menu</SheetTitle>
                </SheetHeader>
                <nav className="mt-4 flex flex-col gap-1">
                    {items.map((item) => {
                        const isActive = url.startsWith(item.href);
                        return (
                            <Link
                                key={item.href}
                                href={item.href}
                                onClick={() => onOpenChange(false)}
                                className={cn(
                                    'flex items-center gap-3 rounded-md px-4 py-3 text-sm font-medium transition-colors',
                                    isActive
                                        ? 'bg-surface-soft text-ink'
                                        : 'text-muted hover:bg-surface-soft hover:text-ink'
                                )}
                            >
                                <item.icon className="h-5 w-5 shrink-0" />
                                {item.name}
                            </Link>
                        );
                    })}
                </nav>
                <div className="mt-6 border-t border-hairline pt-4">
                    <p className="mb-3 text-xs text-muted">Version: {props.app?.version ?? '—'}</p>
                    <div className="flex items-center gap-3">
                        <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-surface-soft text-sm font-medium">
                            {user?.name?.charAt(0)?.toUpperCase()}
                        </div>
                        <div className="min-w-0 flex-1">
                            <p className="truncate text-sm font-medium">{user?.name}</p>
                            <p className="truncate text-xs capitalize text-muted">
                                {user?.role?.replace('_', ' ')}
                            </p>
                        </div>
                        <Link
                            href="/logout"
                            method="post"
                            as="button"
                            className="rounded-md p-2 text-muted hover:bg-surface-soft hover:text-ink"
                            aria-label="Logout"
                        >
                            <LogOut className="h-4 w-4" />
                        </Link>
                    </div>
                </div>
            </SheetContent>
        </Sheet>
    );
}
