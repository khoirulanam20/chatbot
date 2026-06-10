import { useEffect, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { Download, MessageSquare, Search } from 'lucide-react';
import { Layout } from '@/components/Layout';
import { NavPillGroup, NavPillItem } from '@/components/NavPillGroup';
import { Pagination } from '@/components/Pagination';
import { StatusBadge } from '@/components/StatusBadge';
import { Input } from '@/components/ui/input';
import type { Chatbot, Conversation, Paginated } from '@/types';

interface Props {
    conversations: Paginated<Conversation>;
    chatbots: Chatbot[];
    filters: { status?: string; channel?: string; search?: string; chatbot_id?: string };
}

export default function ConversationsIndex({ conversations, filters }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [status, setStatus] = useState(filters.status ?? '');
    const [channel, setChannel] = useState(filters.channel ?? '');

    useEffect(() => {
        const interval = setInterval(() => {
            router.reload({ only: ['conversations'], preserveState: true, preserveScroll: true });
        }, 5000);
        return () => clearInterval(interval);
    }, []);

    useEffect(() => {
        const timeout = setTimeout(() => {
            router.get(
                '/admin/conversations',
                {
                    search: search || undefined,
                    status: status || undefined,
                    channel: channel || undefined,
                },
                { preserveState: true, replace: true }
            );
        }, 300);
        return () => clearTimeout(timeout);
    }, [search, status, channel]);

    const exportUrl = `/admin/conversations/export?${new URLSearchParams(
        Object.fromEntries(
            Object.entries({ search, status, channel }).filter(([, v]) => v)
        ) as Record<string, string>
    ).toString()}`;

    return (
        <Layout>
            <Head title="Percakapan" />
            <div className="space-y-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="font-display text-2xl font-semibold text-ink">Percakapan</h1>
                        <p className="mt-1 text-muted">Pantau dan kelola semua percakapan</p>
                    </div>
                    <a href={exportUrl} className="inline-flex items-center gap-2 rounded-md border border-hairline px-4 py-2 text-sm hover:bg-surface-soft">
                        <Download className="h-4 w-4" />
                        Export CSV
                    </a>
                </div>

                <div className="flex flex-wrap items-center gap-4">
                    <div className="relative max-w-md flex-1">
                        <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted" />
                        <Input
                            placeholder="Cari kontak..."
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            className="pl-10"
                        />
                    </div>
                    <NavPillGroup>
                        <NavPillItem active={!status} onClick={() => setStatus('')}>Semua</NavPillItem>
                        <NavPillItem active={status === 'open'} onClick={() => setStatus('open')}>Aktif</NavPillItem>
                        <NavPillItem active={status === 'handoff'} onClick={() => setStatus('handoff')}>Handoff</NavPillItem>
                        <NavPillItem active={status === 'resolved'} onClick={() => setStatus('resolved')}>Selesai</NavPillItem>
                    </NavPillGroup>
                    <select
                        value={channel}
                        onChange={(e) => setChannel(e.target.value)}
                        className="h-10 rounded-md border border-hairline px-3 text-sm"
                    >
                        <option value="">Semua Channel</option>
                        <option value="web">Web</option>
                        <option value="whatsapp">WhatsApp</option>
                    </select>
                </div>

                {conversations.data.length === 0 ? (
                    <div className="rounded-lg bg-surface-card p-12 text-center">
                        <MessageSquare className="mx-auto mb-4 h-12 w-12 text-muted" />
                        <p className="text-muted">Tidak ada percakapan</p>
                    </div>
                ) : (
                    <div className="space-y-2">
                        {conversations.data.map((conv) => (
                            <Link
                                key={conv.id}
                                href={`/admin/conversations/${conv.id}`}
                                className="block rounded-lg border border-hairline bg-surface-card p-4 transition-colors hover:bg-surface-soft"
                            >
                                <div className="flex items-center justify-between">
                                    <div>
                                        <p className="font-medium text-ink">
                                            {conv.contact?.name || conv.contact?.identifier || 'Anonymous'}
                                        </p>
                                        <p className="text-sm text-muted">
                                            {conv.chatbot?.name} · {conv.channel === 'whatsapp' ? 'WhatsApp' : 'Web'}
                                        </p>
                                    </div>
                                    <StatusBadge status={conv.status} />
                                </div>
                            </Link>
                        ))}
                    </div>
                )}
                <Pagination data={conversations} />
            </div>
        </Layout>
    );
}
