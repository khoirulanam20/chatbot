import { Head, Link } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { Layout } from '@/components/Layout';
import { Pagination } from '@/components/Pagination';
import { Button } from '@/components/ui/button';
import type { Paginated, TenantFull } from '@/types';

interface Props {
    tenants: Paginated<TenantFull>;
}

export default function TenantsIndex({ tenants }: Props) {
    return (
        <Layout>
            <Head title="Tenants" />
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="font-display text-2xl font-semibold">Tenants</h1>
                        <p className="text-muted">Kelola organisasi multi-tenant</p>
                    </div>
                    <Button asChild>
                        <Link href="/admin/tenants/create">
                            <Plus className="mr-2 h-4 w-4" />
                            Tambah Tenant
                        </Link>
                    </Button>
                </div>
                <div className="space-y-2">
                    {tenants.data.map((t) => (
                        <div key={t.id} className="flex items-center justify-between rounded-lg border border-hairline bg-surface-card p-4">
                            <div>
                                <p className="font-medium">{t.name}</p>
                                <p className="text-sm text-muted">{t.slug} · {t.plan} · {t.users_count} user · {t.chatbots_count} bot</p>
                            </div>
                            <div className="flex gap-1">
                                <Button variant="ghost" size="icon" asChild>
                                    <Link href={`/admin/tenants/${t.id}/edit`}>
                                        <Pencil className="h-4 w-4" />
                                    </Link>
                                </Button>
                                <Button variant="ghost" size="icon" asChild>
                                    <Link href={`/admin/tenants/${t.id}`} method="delete">
                                        <Trash2 className="h-4 w-4 text-error" />
                                    </Link>
                                </Button>
                            </div>
                        </div>
                    ))}
                </div>
                <Pagination data={tenants} />
            </div>
        </Layout>
    );
}
