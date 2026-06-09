import { Link, router, usePage } from '@inertiajs/react';
import {
    Bot,
    Building2,
    LayoutDashboard,
    LogOut,
    MessageSquare,
    BookOpen,
    Settings,
    Smartphone,
    Users,
    Palette,
} from 'lucide-react';
import { ReactNode, useEffect } from 'react';
import { FlashToast } from '@/components/FlashToast';
import { NotificationBell } from '@/components/NotificationBell';
import type { PageProps } from '@/types';
import { cn } from '@/lib/utils';

interface LayoutProps {
    children: ReactNode;
}

const navItems = [
    { name: 'Dashboard', href: '/admin/dashboard', icon: LayoutDashboard, roles: ['super_admin', 'admin', 'operator', 'viewer'] },
    { name: 'Percakapan', href: '/admin/conversations', icon: MessageSquare, roles: ['super_admin', 'admin', 'operator', 'viewer'] },
    { name: 'Chatbot', href: '/admin/chatbot', icon: Bot, roles: ['super_admin', 'admin', 'operator', 'viewer'] },
    { name: 'Knowledge Base', href: '/admin/knowledge', icon: BookOpen, roles: ['super_admin', 'admin', 'operator', 'viewer'] },
    { name: 'WhatsApp', href: '/admin/wa', icon: Smartphone, roles: ['super_admin', 'admin', 'operator', 'viewer'] },
    { name: 'Pengguna', href: '/admin/users', icon: Users, roles: ['super_admin', 'admin'] },
    { name: 'Tenants', href: '/admin/tenants', icon: Building2, roles: ['super_admin'] },
    { name: 'CMS Landing', href: '/admin/marketing', icon: Palette, roles: ['super_admin'] },
    { name: 'Pengaturan AI', href: '/admin/settings', icon: Settings, roles: ['super_admin', 'admin', 'operator', 'viewer'] },
];

export function Layout({ children }: LayoutProps) {
    const { url, props } = usePage<PageProps>();
    const user = props.auth.user;

    const visibleNav = navItems.filter((item) => user && item.roles.includes(user.role));

    useEffect(() => {
        if (!user) return;
        const interval = setInterval(() => {
            router.reload({ only: ['notifications'] });
        }, 30000);
        return () => clearInterval(interval);
    }, [user?.id]);

    return (
        <div className="min-h-screen bg-canvas">
            <FlashToast />
            <aside className="fixed left-0 top-0 z-30 flex h-full w-64 flex-col bg-surface-dark text-on-dark">
                <div className="border-b border-surface-dark-elevated p-6">
                    <h1 className="font-display text-lg font-semibold tracking-tight">AI CS Chatbot</h1>
                    <p className="mt-1 truncate text-xs text-on-dark-soft">
                        {user?.tenant?.name ?? 'Super Admin'}
                    </p>
                </div>
                <nav className="flex-1 overflow-y-auto px-3 py-4">
                    {visibleNav.map((item) => {
                        const isActive = url.startsWith(item.href);
                        return (
                            <Link
                                key={item.name}
                                href={item.href}
                                className={cn(
                                    'mb-1 flex items-center gap-3 rounded-md px-4 py-3 text-sm font-medium transition-colors',
                                    isActive
                                        ? 'bg-surface-dark-elevated text-on-dark'
                                        : 'text-on-dark-soft hover:bg-surface-dark-elevated hover:text-on-dark'
                                )}
                            >
                                <item.icon className="h-5 w-5" />
                                {item.name}
                            </Link>
                        );
                    })}
                </nav>
                <div className="border-t border-surface-dark-elevated p-4">
                    <p className="mb-3 text-xs text-on-dark-soft">
                        Version: {props.app?.version ?? '—'}
                    </p>
                    <div className="mb-3 flex items-center gap-3">
                        <div className="flex h-9 w-9 items-center justify-center rounded-full bg-surface-dark-elevated text-sm font-medium">
                            {user?.name?.charAt(0)?.toUpperCase()}
                        </div>
                        <div className="min-w-0 flex-1">
                            <p className="truncate text-sm font-medium">{user?.name}</p>
                            <p className="truncate text-xs capitalize text-on-dark-soft">
                                {user?.role?.replace('_', ' ')}
                            </p>
                        </div>
                        <Link
                            href="/logout"
                            method="post"
                            as="button"
                            className="text-on-dark-soft hover:text-on-dark"
                        >
                            <LogOut className="h-4 w-4" />
                        </Link>
                    </div>
                </div>
            </aside>
            <main className="ml-64 min-h-screen">
                <header className="sticky top-0 z-20 flex items-center justify-end border-b border-hairline bg-canvas/95 px-8 py-3 backdrop-blur">
                    <NotificationBell />
                </header>
                <div className="p-8">{children}</div>
            </main>
        </div>
    );
}
