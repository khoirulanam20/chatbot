import { ChangeEventHandler, FormEventHandler, useEffect, useRef, useState } from 'react';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { ArrowLeft, Bot, Headphones, ImagePlus, Send, User } from 'lucide-react';
import { Layout } from '@/components/Layout';
import { StatusBadge } from '@/components/StatusBadge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Conversation, Message, User as AppUser } from '@/types';

interface Props {
    conversation: Conversation & {
        is_ai_active?: boolean;
        idle_expires_at?: string | null;
        assigned_agent_id?: number | null;
    };
    messages: Message[];
    agents: AppUser[];
}

export default function ConversationsShow({ conversation, messages, agents }: Props) {
    const { auth } = usePage().props as { auth: { user: { id: number; role: string } | null } };
    const currentUserId = auth.user?.id;
    const isOperator = auth.user?.role === 'operator' || auth.user?.role === 'admin' || auth.user?.role === 'super_admin';

    const messagesEndRef = useRef<HTMLDivElement>(null);
    const fileInputRef = useRef<HTMLInputElement>(null);
    const { data, setData, post, processing, reset } = useForm({ message: '' });
    const [uploadingImage, setUploadingImage] = useState(false);
    const assignForm = useForm({ agent_id: '' });

    const inHandoff = conversation.is_ai_active === false || conversation.status === 'handoff';
    const waitingForAdmin = inHandoff && !conversation.assigned_agent_id;
    const assignedToMe = conversation.assigned_agent_id === currentUserId;
    const assignedToOther =
        inHandoff && conversation.assigned_agent_id && conversation.assigned_agent_id !== currentUserId;

    const isAdmin = auth.user?.role === 'admin' || auth.user?.role === 'super_admin';

    const canReply =
        isOperator &&
        (conversation.is_ai_active === true ||
            waitingForAdmin ||
            assignedToMe ||
            (isAdmin && inHandoff));

    const canTakeOver =
        isOperator && inHandoff && (waitingForAdmin || (assignedToOther && isAdmin));

    useEffect(() => {
        const interval = setInterval(() => {
            router.reload({ only: ['messages', 'conversation'], preserveScroll: true });
        }, 3000);
        return () => clearInterval(interval);
    }, []);

    useEffect(() => {
        messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    }, [messages]);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        if (!data.message.trim()) return;
        post(`/admin/conversations/${conversation.id}/message`, {
            onSuccess: () => reset('message'),
        });
    };

    const onImageSelected: ChangeEventHandler<HTMLInputElement> = (e) => {
        const file = e.target.files?.[0];
        e.target.value = '';
        if (!file || file.size > 10 * 1024 * 1024) return;

        const formData = new FormData();
        formData.append('image', file);
        if (data.message.trim()) {
            formData.append('caption', data.message.trim());
        }

        setUploadingImage(true);
        router.post(`/admin/conversations/${conversation.id}/image`, formData, {
            forceFormData: true,
            preserveScroll: true,
            onFinish: () => setUploadingImage(false),
            onSuccess: () => reset('message'),
        });
    };

    const getRoleIcon = (role: string) => {
        if (role === 'user') return <User className="h-4 w-4" />;
        if (role === 'agent') return <Headphones className="h-4 w-4" />;
        return <Bot className="h-4 w-4" />;
    };

    return (
        <Layout>
            <Head title="Detail Percakapan" />
            <div className="space-y-6">
                <Link href="/admin/conversations" className="inline-flex items-center gap-2 text-sm text-muted hover:text-ink">
                    <ArrowLeft className="h-4 w-4" /> Kembali
                </Link>

                <div className="grid gap-6 lg:grid-cols-3">
                    <div className="flex flex-col rounded-lg border border-hairline bg-surface-card lg:col-span-2" style={{ height: 600 }}>
                        <div className="flex items-center justify-between border-b border-hairline p-4">
                            <div>
                                <p className="font-semibold">
                                    {conversation.contact?.name || conversation.contact?.identifier || 'Anonymous'}
                                </p>
                                <p className="text-xs text-muted">
                                    {conversation.channel === 'whatsapp' ? 'WhatsApp' : 'Web'} · {conversation.chatbot?.name}
                                </p>
                            </div>
                            <div className="flex items-center gap-2">
                                {waitingForAdmin && (
                                    <span className="rounded-full bg-warning/20 px-2 py-0.5 text-xs font-medium text-warning">
                                        Menunggu admin
                                    </span>
                                )}
                                {inHandoff && assignedToMe && (
                                    <span className="rounded-full bg-warning/20 px-2 py-0.5 text-xs font-medium text-warning">
                                        Anda menangani
                                    </span>
                                )}
                                {assignedToOther && (
                                    <span className="rounded-full bg-muted/20 px-2 py-0.5 text-xs font-medium text-muted">
                                        Ditangani agen lain
                                    </span>
                                )}
                                <StatusBadge status={conversation.status} />
                            </div>
                        </div>
                        <div className="flex-1 space-y-4 overflow-y-auto p-5">
                            {messages.map((msg) => (
                                <div key={msg.id} className={`flex gap-3 ${msg.role === 'user' ? 'justify-end' : ''}`}>
                                    {msg.role !== 'user' && (
                                        <div className="flex h-8 w-8 items-center justify-center rounded-full bg-surface-soft">
                                            {getRoleIcon(msg.role)}
                                        </div>
                                    )}
                                    <div
                                        className={`max-w-md rounded-lg px-4 py-2 text-sm ${
                                            msg.role === 'user'
                                                ? 'bg-primary text-on-primary'
                                                : msg.role === 'agent'
                                                  ? 'bg-warning/20 text-ink'
                                                  : 'border border-hairline bg-canvas'
                                        }`}
                                    >
                                        {msg.role === 'agent' && <p className="mb-1 text-xs font-medium text-warning">Agen</p>}
                                        {msg.metadata?.type === 'image' && msg.metadata.url && (
                                            <img
                                                src={msg.metadata.url}
                                                alt="Lampiran"
                                                className="mb-2 max-h-48 rounded-md object-contain"
                                            />
                                        )}
                                        {msg.content && msg.content !== '[Gambar]' && (
                                            <p className="whitespace-pre-wrap">{msg.content}</p>
                                        )}
                                        <p className="mt-1 text-xs opacity-60">{msg.created_at}</p>
                                    </div>
                                </div>
                            ))}
                            <div ref={messagesEndRef} />
                        </div>
                        {canReply && (
                            <form onSubmit={submit} className="flex items-center gap-2 border-t border-hairline p-4">
                                <input
                                    ref={fileInputRef}
                                    type="file"
                                    accept="image/jpeg,image/png,image/gif,image/webp"
                                    className="hidden"
                                    onChange={onImageSelected}
                                />
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="icon"
                                    className="shrink-0"
                                    disabled={processing || uploadingImage}
                                    onClick={() => fileInputRef.current?.click()}
                                    aria-label="Kirim gambar"
                                >
                                    <ImagePlus className="h-4 w-4" />
                                </Button>
                                <Input
                                    value={data.message}
                                    onChange={(e) => setData('message', e.target.value)}
                                    placeholder="Ketik balasan sebagai agen..."
                                    className="flex-1"
                                />
                                <Button type="submit" disabled={processing || uploadingImage} className="shrink-0">
                                    <Send className="h-4 w-4" />
                                </Button>
                            </form>
                        )}
                    </div>

                    <div className="space-y-4">
                        <div className="rounded-lg border border-hairline bg-surface-card p-5">
                            <h3 className="mb-3 font-semibold">Info Kontak</h3>
                            <dl className="space-y-2 text-sm">
                                <div className="flex justify-between">
                                    <dt className="text-muted">Identifier</dt>
                                    <dd>{conversation.contact?.identifier ?? '-'}</dd>
                                </div>
                                <div className="flex justify-between">
                                    <dt className="text-muted">Channel</dt>
                                    <dd>{conversation.channel}</dd>
                                </div>
                                <div className="flex justify-between">
                                    <dt className="text-muted">Total Pesan</dt>
                                    <dd>{messages.length}</dd>
                                </div>
                                {inHandoff && conversation.idle_expires_at && (
                                    <div className="flex justify-between">
                                        <dt className="text-muted">AI aktif kembali</dt>
                                        <dd className="text-right text-xs">
                                            {new Date(conversation.idle_expires_at).toLocaleString('id-ID')}
                                        </dd>
                                    </div>
                                )}
                            </dl>
                        </div>
                        <div className="rounded-lg border border-hairline bg-surface-card p-5 space-y-2">
                            <h3 className="mb-3 font-semibold">Aksi</h3>
                            {canTakeOver && (
                                <Button
                                    className="w-full"
                                    onClick={() =>
                                        router.post(`/admin/conversations/${conversation.id}/take-over`)
                                    }
                                >
                                    Ambil Alih
                                </Button>
                            )}
                            <Button
                                variant="secondary"
                                className="w-full"
                                onClick={() =>
                                    router.patch(`/admin/conversations/${conversation.id}/status`, { status: 'resolved' })
                                }
                            >
                                Tandai Selesai
                            </Button>
                            <form
                                onSubmit={(e) => {
                                    e.preventDefault();
                                    assignForm.post(`/admin/conversations/${conversation.id}/assign`);
                                }}
                                className="space-y-2"
                            >
                                <Label>Assign ke Agen</Label>
                                <select
                                    value={assignForm.data.agent_id}
                                    onChange={(e) => assignForm.setData('agent_id', e.target.value)}
                                    className="flex h-10 w-full rounded-md border border-hairline px-3 text-sm"
                                >
                                    <option value="">Pilih agen...</option>
                                    {agents.map((a) => (
                                        <option key={a.id} value={a.id}>{a.name}</option>
                                    ))}
                                </select>
                                <Button type="submit" variant="outline" className="w-full" disabled={assignForm.processing}>
                                    Assign
                                </Button>
                            </form>
                            {inHandoff && (
                                <Button variant="outline" className="w-full" asChild>
                                    <Link href={`/admin/conversations/${conversation.id}/resume-ai`} method="post">
                                        Aktifkan AI Kembali
                                    </Link>
                                </Button>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </Layout>
    );
}
