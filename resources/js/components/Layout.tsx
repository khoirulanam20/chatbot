import { Link, router, usePage } from '@inertiajs/react';
import { LogOut } from 'lucide-react';
import { ReactNode, useEffect, useState } from 'react';
import { AdminBottomNav } from '@/components/admin/AdminBottomNav';
import { AdminMobileHeader } from '@/components/admin/AdminMobileHeader';
import { AdminMobileMenu } from '@/components/admin/AdminMobileMenu';
import { FlashToast } from '@/components/FlashToast';
import { NotificationBell } from '@/components/NotificationBell';
import { getVisibleNav } from '@/config/adminNav';
import type { PageProps } from '@/types';
import { cn } from '@/lib/utils';

interface LayoutProps {
    children: ReactNode;
}

export function Layout({ children }: LayoutProps) {
    const { url, props } = usePage<PageProps>();
    const user = props.auth.user;
    const [menuOpen, setMenuOpen] = useState(false);

    const visibleNav = getVisibleNav(user?.role);

    useEffect(() => {
        document.body.classList.add('theme-light');

        return () => {
            document.body.classList.remove('theme-light');
        };
    }, []);

    useEffect(() => {
        if (!user) return;
        const interval = setInterval(() => {
            router.reload({ only: ['notifications'] });
        }, 30000);
        return () => clearInterval(interval);
    }, [user?.id]);

    return (
        <div className="admin-theme-light min-h-screen bg-canvas text-ink">
            <FlashToast />
            <aside className="fixed left-0 top-0 z-30 hidden h-full w-72 flex-col border-r border-hairline bg-surface-card text-ink shadow-admin backdrop-blur lg:flex">
                <div className="border-b border-hairline px-6 py-7">
                    <p className="mb-2 text-[11px] font-semibold tracking-[0.24em] text-primary uppercase">
                        Admin Panel
                    </p>
                    <h1 className="font-display text-xl font-semibold tracking-tight">AI CS Chatbot</h1>
                    <p className="mt-1 truncate text-xs text-muted">
                        {user?.tenant?.name ?? 'Super Admin'}
                    </p>
                </div>
                <nav className="flex-1 overflow-y-auto px-4 py-5">
                    {visibleNav.map((item) => {
                        const isActive = url.startsWith(item.href);
                        return (
                            <Link
                                key={item.name}
                                href={item.href}
                                className={cn(
                                    'mb-1.5 flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium transition-colors',
                                    isActive
                                        ? 'bg-surface-soft text-ink shadow-sm ring-1 ring-primary/10'
                                        : 'text-muted hover:bg-surface-soft hover:text-ink'
                                )}
                            >
                                <item.icon className="h-5 w-5" />
                                {item.name}
                            </Link>
                        );
                    })}
                </nav>
                <div className="border-t border-hairline p-4">
                    <p className="mb-3 text-xs text-muted">
                        Version: {props.app?.version ?? '—'}
                    </p>
                    <div className="mb-3 flex items-center gap-3 rounded-2xl bg-surface-soft p-3">
                        <div className="flex h-10 w-10 items-center justify-center rounded-full bg-primary/10 text-sm font-semibold text-primary ring-1 ring-primary/10">
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
                            className="rounded-lg p-2 text-muted transition-colors hover:bg-white hover:text-ink"
                        >
                            <LogOut className="h-4 w-4" />
                        </Link>
                    </div>
                </div>
            </aside>
            <main className="min-h-screen pb-20 lg:ml-72 lg:pb-0">
                <header className="sticky top-0 z-20 hidden items-center justify-end border-b border-hairline bg-canvas/95 px-8 py-4 backdrop-blur lg:flex">
                    <NotificationBell />
                </header>
                <AdminMobileHeader />
                <div className="mx-auto w-full max-w-[1440px] p-4 lg:p-8">{children}</div>
            </main>
            <AdminBottomNav onMenuOpen={() => setMenuOpen(true)} />
            <AdminMobileMenu open={menuOpen} onOpenChange={setMenuOpen} />
        </div>
    );
}
