import { FormEventHandler } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { Layout } from '@/components/Layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Chatbot } from '@/types';

interface Props {
    chatbot: Chatbot & {
        system_prompt?: string;
        fallback_message?: string;
        handoff_triggers?: string[];
        temperature?: number;
        max_context?: number;
        language?: string;
        avatar?: string;
        embed_config?: {
            primary_color?: string;
            position?: string;
            greeting?: string;
            quick_replies?: string[];
        };
        settings?: {
            agent_session_minutes?: number;
            agent_session_message?: string;
        };
        embedConfig?: {
            primary_color?: string;
            position?: string;
            greeting?: string;
            quick_replies?: string[];
            allow_file_upload?: boolean;
        };
    };
    tenants: unknown[];
}

export default function ChatbotEdit({ chatbot }: Props) {
    const embed = chatbot.embed_config ?? chatbot.embedConfig;
    const { data, setData, post, processing, errors } = useForm({
        _method: 'put',
        name: chatbot.name ?? '',
        model: chatbot.model ?? 'gpt-4o',
        temperature: String(chatbot.temperature ?? 0.7),
        max_context: String(chatbot.max_context ?? 10),
        language: chatbot.language ?? 'id',
        system_prompt: chatbot.system_prompt ?? '',
        fallback_message: chatbot.fallback_message ?? '',
        handoff_triggers: Array.isArray(chatbot.handoff_triggers)
            ? chatbot.handoff_triggers.join('\n')
            : '',
        is_active: chatbot.is_active ?? true,
        avatar: null as File | null,
        primary_color: embed?.primary_color ?? '#4F46E5',
        position: embed?.position ?? 'bottom-right',
        greeting: embed?.greeting ?? '',
        quick_replies: Array.isArray(embed?.quick_replies) ? embed.quick_replies.join('\n') : '',
        allow_file_upload: embed?.allow_file_upload ?? false,
        agent_session_minutes: String(chatbot.settings?.agent_session_minutes ?? 30),
        agent_session_message: chatbot.settings?.agent_session_message ?? '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(`/admin/chatbot/${chatbot.id}`, { forceFormData: true });
    };

    return (
        <Layout>
            <Head title={`Edit — ${chatbot.name}`} />
            <div className="mx-auto max-w-3xl space-y-6">
                <Link href="/admin/chatbot" className="text-sm text-muted hover:text-ink">
                    ← Kembali
                </Link>
                <div className="rounded-lg border border-hairline bg-surface-card p-6">
                    <h1 className="mb-6 font-display text-lg font-semibold">Konfigurasi Chatbot</h1>
                    <form onSubmit={submit} className="space-y-4">
                        <div className="grid gap-4 md:grid-cols-2">
                            <div>
                                <Label>Nama *</Label>
                                <Input value={data.name} onChange={(e) => setData('name', e.target.value)} className="mt-1" required />
                                {errors.name && <p className="text-sm text-error">{errors.name}</p>}
                            </div>
                            <div>
                                <Label>Avatar</Label>
                                {chatbot.avatar && (
                                    <img src={`/storage/${chatbot.avatar}`} alt="" className="mb-2 h-12 w-12 rounded-lg object-cover" />
                                )}
                                <Input type="file" accept="image/*" className="mt-1" onChange={(e) => setData('avatar', e.target.files?.[0] ?? null)} />
                            </div>
                            <div>
                                <Label>Model AI *</Label>
                                <Input value={data.model} onChange={(e) => setData('model', e.target.value)} className="mt-1" required />
                            </div>
                            <div>
                                <Label>Bahasa</Label>
                                <select value={data.language} onChange={(e) => setData('language', e.target.value)} className="mt-1 flex h-10 w-full rounded-md border border-hairline px-3 text-sm">
                                    <option value="id">Indonesia</option>
                                    <option value="en">English</option>
                                </select>
                            </div>
                            <div>
                                <Label>Temperature</Label>
                                <Input type="number" step="0.1" min="0" max="1" value={data.temperature} onChange={(e) => setData('temperature', e.target.value)} className="mt-1" />
                            </div>
                            <div>
                                <Label>Max Context</Label>
                                <Input type="number" min="1" max="50" value={data.max_context} onChange={(e) => setData('max_context', e.target.value)} className="mt-1" />
                            </div>
                        </div>
                        <div>
                            <Label>System Prompt</Label>
                            <textarea rows={5} value={data.system_prompt} onChange={(e) => setData('system_prompt', e.target.value)} className="mt-1 w-full rounded-md border border-hairline px-3 py-2 text-sm" />
                        </div>
                        <div>
                            <Label>Pesan Fallback</Label>
                            <Input value={data.fallback_message} onChange={(e) => setData('fallback_message', e.target.value)} className="mt-1" />
                        </div>
                        <div>
                            <Label>Kata Kunci Handoff</Label>
                            <textarea rows={3} value={data.handoff_triggers} onChange={(e) => setData('handoff_triggers', e.target.value)} className="mt-1 w-full rounded-md border border-hairline px-3 py-2 text-sm" />
                        </div>
                        <hr className="border-hairline" />
                        <h2 className="font-semibold">Sesi Agen</h2>
                        <p className="text-sm text-muted">
                            Saat agen ditugaskan atau membalas, AI tidak ikut campur hingga durasi sesi berakhir (lalu AI aktif kembali otomatis).
                        </p>
                        <div className="grid gap-4 md:grid-cols-2">
                            <div>
                                <Label>Durasi sesi agen (menit)</Label>
                                <Input
                                    type="number"
                                    min={1}
                                    max={1440}
                                    value={data.agent_session_minutes}
                                    onChange={(e) => setData('agent_session_minutes', e.target.value)}
                                    className="mt-1"
                                />
                            </div>
                        </div>
                        <div>
                            <Label>Pesan tunggu agen</Label>
                            <Input
                                value={data.agent_session_message}
                                onChange={(e) => setData('agent_session_message', e.target.value)}
                                className="mt-1"
                                placeholder="Agen kami sedang menangani percakapan Anda..."
                            />
                        </div>
                        <label className="flex items-center gap-2 text-sm">
                            <input type="checkbox" checked={data.is_active} onChange={(e) => setData('is_active', e.target.checked)} />
                            Chatbot aktif
                        </label>
                        <hr className="border-hairline" />
                        <h2 className="font-semibold">Konfigurasi Widget Embed</h2>
                        <div className="grid gap-4 md:grid-cols-2">
                            <div>
                                <Label>Warna Utama</Label>
                                <Input type="color" value={data.primary_color} onChange={(e) => setData('primary_color', e.target.value)} className="mt-1 h-10 w-20" />
                            </div>
                            <div>
                                <Label>Posisi</Label>
                                <select value={data.position} onChange={(e) => setData('position', e.target.value)} className="mt-1 flex h-10 w-full rounded-md border border-hairline px-3 text-sm">
                                    <option value="bottom-right">Kanan Bawah</option>
                                    <option value="bottom-left">Kiri Bawah</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <Label>Pesan Sambutan</Label>
                            <Input value={data.greeting} onChange={(e) => setData('greeting', e.target.value)} className="mt-1" />
                        </div>
                        <div>
                            <Label>Quick Replies</Label>
                            <textarea rows={4} value={data.quick_replies} onChange={(e) => setData('quick_replies', e.target.value)} className="mt-1 w-full rounded-md border border-hairline px-3 py-2 text-sm" />
                        </div>
                        <label className="flex items-center gap-2 text-sm">
                            <input
                                type="checkbox"
                                checked={data.allow_file_upload}
                                onChange={(e) => setData('allow_file_upload', e.target.checked)}
                            />
                            Izinkan pengunjung mengirim gambar di widget
                        </label>
                        <div className="flex gap-3">
                            <Button type="submit" disabled={processing}>Simpan Perubahan</Button>
                            <Button type="button" variant="outline" asChild>
                                <Link href={`/admin/chatbot/${chatbot.id}/embed-code`}>
                                    Lihat Embed Code
                                </Link>
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </Layout>
    );
}
