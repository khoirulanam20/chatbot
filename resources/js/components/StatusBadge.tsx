import { Badge } from '@/components/ui/badge';

const statusConfig: Record<string, { label: string; variant: 'default' | 'secondary' | 'success' | 'warning' | 'destructive' }> = {
    open: { label: 'Aktif', variant: 'default' },
    handoff: { label: 'Handoff', variant: 'warning' },
    resolved: { label: 'Selesai', variant: 'success' },
    spam: { label: 'Spam', variant: 'destructive' },
    active: { label: 'Aktif', variant: 'success' },
    inactive: { label: 'Nonaktif', variant: 'secondary' },
    error: { label: 'Error', variant: 'destructive' },
    queued: { label: 'Antrian', variant: 'warning' },
    processing: { label: 'Memproses', variant: 'default' },
    indexed: { label: 'Terindeks', variant: 'success' },
    failed: { label: 'Gagal', variant: 'destructive' },
};

interface StatusBadgeProps {
    status: string;
}

export function StatusBadge({ status }: StatusBadgeProps) {
    const config = statusConfig[status] ?? { label: status, variant: 'secondary' as const };
    return <Badge variant={config.variant}>{config.label}</Badge>;
}
