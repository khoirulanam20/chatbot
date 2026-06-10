import { Link, router, usePage } from '@inertiajs/react';
import { Bell } from 'lucide-react';
import { useState } from 'react';
import type { PageProps } from '@/types';
import { cn } from '@/lib/utils';

interface NotificationItem {
    id: string;
    title: string;
    body: string;
    url: string;
    read_at?: string | null;
    created_at: string;
}

export function NotificationBell() {
    const { props } = usePage<PageProps & {
        notifications?: { unread_count: number; recent: NotificationItem[] };
    }>();
    const [open, setOpen] = useState(false);
    const unread = props.notifications?.unread_count ?? 0;
    const recent = props.notifications?.recent ?? [];

    const markRead = (id: string) => {
        router.post(`/admin/notifications/${id}/read`, {}, { preserveScroll: true });
        setOpen(false);
    };

    return (
        <div className="relative">
            <button
                type="button"
                onClick={() => setOpen(!open)}
                className="relative rounded-md p-2 text-muted hover:bg-surface-soft hover:text-ink"
                aria-label="Notifikasi"
            >
                <Bell className="h-5 w-5" />
                {unread > 0 && (
                    <span className="absolute right-1 top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-error px-1 text-[10px] font-bold text-on-primary">
                        {unread > 9 ? '9+' : unread}
                    </span>
                )}
            </button>
            {open && (
                <>
                    <div className="fixed inset-0 z-40" onClick={() => setOpen(false)} />
                    <div className="absolute right-0 z-50 mt-2 w-[min(20rem,calc(100vw-2rem))] rounded-lg border border-hairline bg-canvas shadow-lg">
                        <div className="flex items-center justify-between border-b border-hairline px-4 py-3">
                            <span className="font-semibold text-sm">Notifikasi</span>
                            <Link href="/admin/notifications" className="text-xs text-muted hover:text-ink" onClick={() => setOpen(false)}>
                                Lihat semua
                            </Link>
                        </div>
                        <div className="max-h-80 overflow-y-auto">
                            {recent.length === 0 ? (
                                <p className="p-4 text-center text-sm text-muted">Belum ada notifikasi</p>
                            ) : (
                                recent.map((n) => (
                                    <button
                                        key={n.id}
                                        type="button"
                                        onClick={() => {
                                            if (!n.read_at) markRead(n.id);
                                            router.visit(n.url);
                                            setOpen(false);
                                        }}
                                        className={cn(
                                            'block w-full border-b border-hairline px-4 py-3 text-left text-sm hover:bg-surface-soft',
                                            !n.read_at && 'bg-surface-soft/50'
                                        )}
                                    >
                                        <p className="font-medium">{n.title}</p>
                                        <p className="mt-0.5 text-xs text-muted line-clamp-2">{n.body}</p>
                                        <p className="mt-1 text-xs text-muted">{n.created_at}</p>
                                    </button>
                                ))
                            )}
                        </div>
                    </div>
                </>
            )}
        </div>
    );
}
