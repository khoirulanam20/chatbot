import { Link, usePage } from '@inertiajs/react';
import { getPrimaryNav } from '@/config/adminNav';
import type { PageProps } from '@/types';
import { cn } from '@/lib/utils';

export function AdminBottomNav() {
    const { url, props } = usePage<PageProps>();
    const user = props.auth.user;
    const items = getPrimaryNav(user?.role);

    if (items.length === 0) return null;

    return (
        <nav
            className="fixed inset-x-0 bottom-0 z-40 border-t border-hairline bg-canvas/95 backdrop-blur lg:hidden"
            style={{ paddingBottom: 'env(safe-area-inset-bottom, 0px)' }}
            aria-label="Navigasi utama"
        >
            <div className="flex items-stretch justify-around">
                {items.map((item) => {
                    const isActive = url.startsWith(item.href);
                    return (
                        <Link
                            key={item.href}
                            href={item.href}
                            className={cn(
                                'flex min-w-0 flex-1 flex-col items-center gap-0.5 px-1 py-2 text-[10px] font-medium transition-colors',
                                isActive ? 'text-primary' : 'text-muted hover:text-ink'
                            )}
                        >
                            <item.icon className={cn('h-5 w-5 shrink-0', isActive && 'text-primary')} />
                            <span className="truncate">{item.shortLabel ?? item.name}</span>
                        </Link>
                    );
                })}
            </div>
        </nav>
    );
}
