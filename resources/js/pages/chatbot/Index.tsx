import { Head, Link } from '@inertiajs/react';
import { Code, Pencil, Plus, Trash2, UserCircle } from 'lucide-react';
import { Layout } from '@/components/Layout';
import { DataTable } from '@/components/DataTable';
import { Pagination } from '@/components/Pagination';
import { StatusBadge } from '@/components/StatusBadge';
import { Button } from '@/components/ui/button';
import type { Chatbot, Paginated } from '@/types';

interface Props {
    chatbots: Paginated<Chatbot>;
}

export default function ChatbotIndex({ chatbots }: Props) {
    const columns = [
        {
            key: 'name',
            label: 'Nama',
            render: (row: Chatbot) => (
                <span className="font-medium">{row.name}</span>
            ),
        },
        {
            key: 'status',
            label: 'Status',
            render: (row: Chatbot) => (
                <StatusBadge status={row.is_active ? 'active' : 'inactive'} />
            ),
        },
        {
            key: 'actions',
            label: 'Aksi',
            render: (row: Chatbot) => (
                <div className="flex gap-1">
                    <Button variant="ghost" size="icon" asChild>
                        <Link href={`/admin/chatbot/${row.id}/embed-code`}>
                            <Code className="h-4 w-4" />
                        </Link>
                    </Button>
                    <Button variant="ghost" size="icon" asChild title="Persona">
                        <Link href={`/admin/chatbot/${row.id}/persona`}>
                            <UserCircle className="h-4 w-4" />
                        </Link>
                    </Button>
                    <Button variant="ghost" size="icon" asChild>
                        <Link href={`/admin/chatbot/${row.id}/edit`}>
                            <Pencil className="h-4 w-4" />
                        </Link>
                    </Button>
                    <Button variant="ghost" size="icon" asChild>
                        <Link href={`/admin/chatbot/${row.id}`} method="delete">
                            <Trash2 className="h-4 w-4 text-error" />
                        </Link>
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <Layout>
            <Head title="Chatbot" />
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="font-display text-2xl font-semibold text-ink">Chatbot</h1>
                        <p className="mt-1 text-muted">Kelola konfigurasi chatbot</p>
                    </div>
                    <Button asChild>
                        <Link href="/admin/chatbot/create">
                            <Plus className="mr-2 h-4 w-4" />
                            Buat Chatbot
                        </Link>
                    </Button>
                </div>
                <div className="rounded-lg border border-hairline bg-surface-card p-6">
                    <DataTable columns={columns} data={chatbots.data} />
                    <Pagination data={chatbots} />
                </div>
            </div>
        </Layout>
    );
}
