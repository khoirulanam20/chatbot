import { useEffect, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { Calendar, Download, MessageSquare, Phone, Search, Globe } from 'lucide-react';
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
            <div className="space-y-4 lg:space-y-6">
                {/* Header — stack on mobile */}
                <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h1 className="text-xl font-semibold text-ink lg:text-2xl">Percakapan</h1>
                        <p className="mt-0.5 text-sm text-muted">Pantau dan kelola semua percakapan</p>
                    </div>
                    <a
                        href={exportUrl}
                        className="inline-flex items-center gap-2 self-start rounded-lg border border-hairline px-3 py-2 text-sm hover:bg-surface-soft"
                    >
                        <Download className="h-4 w-4" />
                        <span className="hidden sm:inline">Export CSV</span>
                    </a>
                </div>

                {/* Filters — wrap cleanly */}
                <div className="flex flex-col gap-3">
                    <div className="relative w-full">
                        <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted" />
                        <Input
                            placeholder="Cari kontak..."
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            className="pl-10"
                        />
                    </div>
                    <div className="flex gap-2">
                        <NavPillGroup>
                            <NavPillItem active={!status} onClick={() => setStatus('')}>Semua</NavPillItem>
                            <NavPillItem active={status === 'open'} onClick={() => setStatus('open')}>Aktif</NavPillItem>
                            <NavPillItem active={status === 'handoff'} onClick={() => setStatus('handoff')}>Handoff</NavPillItem>
                            <NavPillItem active={status === 'resolved'} onClick={() => setStatus('resolved')}>Selesai</NavPillItem>
                        </NavPillGroup>
                        <select
                            value={channel}
                            onChange={(e) => setChannel(e.target.value)}
                            className="h-10 shrink-0 rounded-md border border-hairline px-3 text-sm"
                        >
                            <option value="">Semua Channel</option>
                            <option value="web">Web</option>
                            <option value="whatsapp">WhatsApp</option>
                        </select>
                    </div>
                </div>

                {/* List */}
                {conversations.data.length === 0 ? (
                    <div className="rounded-lg bg-surface-card p-10 text-center lg:p-12">
                        <MessageSquare className="mx-auto mb-4 h-10 w-10 text-muted lg:h-12 lg:w-12" />
                        <p className="text-sm text-muted lg:text-base">Tidak ada percakapan</p>
                    </div>
                ) : (
                    <div className="space-y-2">
                        {conversations.data.map((conv) => (
                            <Link
                                key={conv.id}
                                href={`/admin/conversations/${conv.id}`}
                                className="block rounded-lg border border-hairline bg-surface-card p-4 transition-colors hover:bg-surface-soft"
                            >
                                <div className="flex items-start justify-between gap-3">
                                    <div className="min-w-0 flex-1">
                                        <p className="truncate text-sm font-medium text-ink">
                                            {conv.contact?.name || conv.contact?.identifier || 'Anonymous'}
                                        </p>
                                        <div className="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted">
                                            <span className="inline-flex items-center gap-1">
                                                {conv.channel === 'whatsapp' ? (
                                                    <Phone className="h-3 w-3" />
                                                ) : (
                                                    <Globe className="h-3 w-3" />
                                                )}
                                                {conv.channel === 'whatsapp' ? 'WhatsApp' : 'Web'}
                                            </span>
                                            <span>{conv.chatbot?.name}</span>
                                            {conv.last_message_at && (
                                                <span className="inline-flex items-center gap-1">
                                                    <Calendar className="h-3 w-3" />
                                                    {new Date(conv.last_message_at).toLocaleDateString('id-ID', {
                                                        day: 'numeric',
                                                        month: 'short',
                                                        hour: '2-digit',
                                                        minute: '2-digit',
                                                    })}
                                                </span>
                                            )}
                                        </div>
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
