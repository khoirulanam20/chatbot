import { Head, Link, router } from '@inertiajs/react';
import { Layout } from '@/components/Layout';
import { Button } from '@/components/ui/button';
import type { Paginated } from '@/types';
import { cn } from '@/lib/utils';

interface NotificationRow {
    id: string;
    type: string;
    title: string;
    body: string;
    url: string;
    read_at: string | null;
    created_at: string;
}

interface Props {
    notifications: Paginated<NotificationRow>;
}

export default function NotificationsIndex({ notifications }: Props) {
    return (
        <Layout>
            <Head title="Notifikasi" />
            <div className="space-y-6">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h1 className="font-display text-2xl font-semibold">Notifikasi</h1>
                        <p className="text-muted">Pembaruan percakapan dan penugasan agen</p>
                    </div>
                    <Button
                        variant="outline"
                        onClick={() => router.post('/admin/notifications/read-all')}
                    >
                        Tandai semua dibaca
                    </Button>
                </div>
                <div className="divide-y divide-hairline rounded-lg border border-hairline bg-surface-card">
                    {notifications.data.length === 0 ? (
                        <p className="p-8 text-center text-sm text-muted">Belum ada notifikasi.</p>
                    ) : (
                        notifications.data.map((n) => (
                            <Link
                                key={n.id}
                                href={n.url}
                                className={cn(
                                    'block px-5 py-4 hover:bg-surface-soft',
                                    !n.read_at && 'bg-surface-soft/40'
                                )}
                                onClick={() => {
                                    if (!n.read_at) {
                                        router.post(`/admin/notifications/${n.id}/read`, {}, { preserveScroll: true });
                                    }
                                }}
                            >
                                <p className="font-medium">{n.title}</p>
                                <p className="mt-1 text-sm text-muted">{n.body}</p>
                                <p className="mt-2 text-xs text-muted">
                                    {new Date(n.created_at).toLocaleString('id-ID')}
                                </p>
                            </Link>
                        ))
                    )}
                </div>
            </div>
        </Layout>
    );
}
