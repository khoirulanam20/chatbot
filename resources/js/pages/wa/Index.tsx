import { Head, Link } from '@inertiajs/react';
import { Pencil, Plus, Zap } from 'lucide-react';
import { Layout } from '@/components/Layout';
import { Pagination } from '@/components/Pagination';
import { StatusBadge } from '@/components/StatusBadge';
import { Button } from '@/components/ui/button';
import type { Paginated, WaInstance } from '@/types';

interface Props {
    instances: Paginated<WaInstance>;
}

export default function WaIndex({ instances }: Props) {
    return (
        <Layout>
            <Head title="WhatsApp" />
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="font-display text-2xl font-semibold">WhatsApp</h1>
                        <p className="text-muted">Kelola instance WhatsApp Chatery</p>
                    </div>
                    <Button asChild>
                        <Link href="/admin/wa/create">
                            <Plus className="mr-2 h-4 w-4" />
                            Tambah Instance
                        </Link>
                    </Button>
                </div>
                <div className="space-y-3">
                    {instances.data.map((inst) => (
                        <div key={inst.id} className="flex items-center justify-between rounded-lg border border-hairline bg-surface-card p-4">
                            <div className="min-w-0 flex-1">
                                <p className="font-medium">{inst.phone_number}</p>
                                <p className="text-sm text-muted">{inst.chatbot?.name}</p>
                                {inst.instance_id && (
                                    <p className="mt-0.5 font-mono text-xs text-muted">
                                        Instance ID: {inst.instance_id}
                                    </p>
                                )}
                                {inst.status === 'error' && inst.metadata?.last_error && (
                                    <p className="mt-2 text-xs text-error">{inst.metadata.last_error}</p>
                                )}
                            </div>
                            <div className="flex shrink-0 items-center gap-2">
                                <StatusBadge status={inst.status} />
                                <Button variant="outline" size="sm" asChild>
                                    <Link href={`/admin/wa/${inst.id}/test`} method="post">
                                        <Zap className="h-4 w-4" />
                                    </Link>
                                </Button>
                                <Button variant="ghost" size="icon" asChild>
                                    <Link href={`/admin/wa/${inst.id}/edit`}>
                                        <Pencil className="h-4 w-4" />
                                    </Link>
                                </Button>
                            </div>
                        </div>
                    ))}
                </div>
                <Pagination data={instances} />
            </div>
        </Layout>
    );
}
