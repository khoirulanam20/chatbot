import { Head, Link } from '@inertiajs/react';
import { Activity, Bot, MessageSquare, Star } from 'lucide-react';
import {
    Bar,
    BarChart,
    CartesianGrid,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';
import { Layout } from '@/components/Layout';
import { StatsCard } from '@/components/StatsCard';
import { StatusBadge } from '@/components/StatusBadge';
import type { Conversation } from '@/types';

interface DashboardProps {
    stats: Record<string, number>;
    trend: { date: string; count: number }[];
    recentConversations: Conversation[];
}

export default function Dashboard({ stats, trend, recentConversations }: DashboardProps) {
    return (
        <Layout>
            <Head title="Dashboard" />
            <div className="space-y-8">
                <div>
                    <h1 className="font-display text-2xl font-semibold tracking-tight text-ink">Dashboard</h1>
                    <p className="mt-1 text-muted">Ringkasan aktivitas chatbot</p>
                </div>

                <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
                    <StatsCard
                        title="Percakapan Hari Ini"
                        value={stats.total_today?.toLocaleString('id-ID') ?? 0}
                        icon={<MessageSquare className="h-5 w-5" />}
                    />
                    <StatsCard
                        title="Diselesaikan"
                        value={stats.resolved?.toLocaleString('id-ID') ?? 0}
                        subtitle="Status resolved"
                        icon={<Activity className="h-5 w-5" />}
                    />
                    <StatsCard
                        title="Rating Kepuasan"
                        value={stats.avg_rating > 0 ? stats.avg_rating.toFixed(1) : '-'}
                        icon={<Star className="h-5 w-5" />}
                    />
                    <StatsCard
                        title="Total Chatbot"
                        value={stats.total_chatbots ?? 0}
                        icon={<Bot className="h-5 w-5" />}
                    />
                </div>

                <div className="grid gap-6 lg:grid-cols-3">
                    <div className="rounded-lg border border-hairline bg-surface-card p-6 lg:col-span-2">
                        <h2 className="mb-6 font-display text-lg font-semibold text-ink">
                            Tren Percakapan (7 Hari)
                        </h2>
                        <div className="h-64">
                            <ResponsiveContainer width="100%" height="100%">
                                <BarChart data={trend}>
                                    <CartesianGrid strokeDasharray="3 3" stroke="#e5e7eb" />
                                    <XAxis dataKey="date" stroke="#6b7280" fontSize={12} />
                                    <YAxis stroke="#6b7280" fontSize={12} />
                                    <Tooltip />
                                    <Bar dataKey="count" fill="#111111" radius={[4, 4, 0, 0]} />
                                </BarChart>
                            </ResponsiveContainer>
                        </div>
                    </div>
                    <div className="rounded-lg border border-hairline bg-surface-card p-6">
                        <h2 className="mb-4 font-display text-lg font-semibold text-ink">Channel Hari Ini</h2>
                        <div className="space-y-3">
                            <div className="flex items-center justify-between rounded-lg bg-surface-soft p-3">
                                <span className="text-sm">Web Widget</span>
                                <span className="font-semibold">{stats.web_count ?? 0}</span>
                            </div>
                            <div className="flex items-center justify-between rounded-lg bg-surface-soft p-3">
                                <span className="text-sm">WhatsApp</span>
                                <span className="font-semibold">{stats.wa_count ?? 0}</span>
                            </div>
                            <div className="flex items-center justify-between rounded-lg bg-surface-soft p-3 text-sm">
                                <span>Handoff</span>
                                <span className="font-semibold">{stats.handoff ?? 0}</span>
                            </div>
                            <div className="flex items-center justify-between rounded-lg bg-surface-soft p-3 text-sm">
                                <span>Dokumen Terindeks</span>
                                <span className="font-semibold">{stats.total_documents ?? 0}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div className="rounded-lg border border-hairline bg-surface-card p-6">
                    <h2 className="mb-6 font-display text-lg font-semibold text-ink">Percakapan Terbaru</h2>
                    <div className="space-y-3">
                        {recentConversations.length === 0 ? (
                            <p className="text-sm text-muted">Belum ada percakapan</p>
                        ) : (
                            recentConversations.map((conv) => (
                                <Link
                                    key={conv.id}
                                    href={`/admin/conversations/${conv.id}`}
                                    className="flex items-center justify-between rounded-lg border border-hairline bg-canvas p-4 transition-colors hover:bg-surface-soft"
                                >
                                    <div>
                                        <p className="font-medium text-ink">
                                            {conv.contact?.name || conv.contact?.identifier || 'Anonymous'}
                                        </p>
                                        <p className="text-sm text-muted">
                                            {conv.chatbot?.name} · {conv.channel}
                                        </p>
                                    </div>
                                    <StatusBadge status={conv.status} />
                                </Link>
                            ))
                        )}
                    </div>
                </div>
            </div>
        </Layout>
    );
}
