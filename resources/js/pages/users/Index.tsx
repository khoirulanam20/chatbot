import { Head, Link } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { Layout } from '@/components/Layout';
import { DataTable } from '@/components/DataTable';
import { Pagination } from '@/components/Pagination';
import { Button } from '@/components/ui/button';
import type { Paginated, User } from '@/types';

interface Props {
    users: Paginated<User>;
}

export default function UsersIndex({ users }: Props) {
    const columns = [
        { key: 'name', label: 'Nama', render: (r: User) => <span className="font-medium">{r.name}</span> },
        { key: 'email', label: 'Email' },
        { key: 'role', label: 'Role', render: (r: User) => r.role.replace('_', ' ') },
        { key: 'tenant', label: 'Tenant', render: (r: User) => r.tenant?.name ?? '-' },
        {
            key: 'actions',
            label: 'Aksi',
            render: (r: User) => (
                <div className="flex gap-1">
                    <Button variant="ghost" size="icon" asChild>
                        <Link href={`/admin/users/${r.id}/edit`}>
                            <Pencil className="h-4 w-4" />
                        </Link>
                    </Button>
                    <Button variant="ghost" size="icon" asChild>
                        <Link href={`/admin/users/${r.id}`} method="delete">
                            <Trash2 className="h-4 w-4 text-error" />
                        </Link>
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <Layout>
            <Head title="Pengguna" />
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="font-display text-2xl font-semibold">Pengguna</h1>
                        <p className="text-muted">Kelola akun admin dan operator</p>
                    </div>
                    <Button asChild>
                        <Link href="/admin/users/create">
                            <Plus className="mr-2 h-4 w-4" />
                            Tambah User
                        </Link>
                    </Button>
                </div>
                <div className="rounded-lg border border-hairline bg-surface-card p-6">
                    <DataTable columns={columns} data={users.data} />
                    <Pagination data={users} />
                </div>
            </div>
        </Layout>
    );
}
