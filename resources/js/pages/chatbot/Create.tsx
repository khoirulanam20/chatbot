import { FormEventHandler } from 'react';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { Layout } from '@/components/Layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { PageProps, Tenant } from '@/types';

interface Props {
    tenants: Tenant[];
}

export default function ChatbotCreate({ tenants }: Props) {
    const { auth } = usePage<PageProps>().props;
    const isSuperAdmin = auth.user?.role === 'super_admin';

    const { data, setData, post, processing, errors } = useForm({
        tenant_id: isSuperAdmin ? String(tenants[0]?.id ?? '') : String(auth.user?.tenant_id ?? ''),
        name: '',
        model: 'gpt-4o',
        temperature: '0.7',
        max_context: '10',
        language: 'id',
        system_prompt: '',
        fallback_message: '',
        handoff_triggers: '',
        is_active: true,
        avatar: null as File | null,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/admin/chatbot', { forceFormData: true });
    };

    return (
        <Layout>
            <Head title="Buat Chatbot" />
            <div className="mx-auto max-w-3xl space-y-6">
                <Link href="/admin/chatbot" className="text-sm text-muted hover:text-ink">
                    ← Kembali
                </Link>
                <div className="rounded-lg border border-hairline bg-surface-card p-6">
                    <h1 className="mb-6 font-display text-lg font-semibold">Buat Chatbot Baru</h1>
                    <form onSubmit={submit} className="space-y-4">
                        {isSuperAdmin && (
                            <div>
                                <Label>Tenant *</Label>
                                <select
                                    value={data.tenant_id}
                                    onChange={(e) => setData('tenant_id', e.target.value)}
                                    className="mt-1 flex h-10 w-full rounded-md border border-hairline bg-canvas px-3 text-sm"
                                    required
                                >
                                    {tenants.map((t) => (
                                        <option key={t.id} value={t.id}>
                                            {t.name}
                                        </option>
                                    ))}
                                </select>
                            </div>
                        )}
                        <div className="grid gap-4 md:grid-cols-2">
                            <div>
                                <Label>Nama Chatbot *</Label>
                                <Input
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    className="mt-1"
                                    required
                                />
                                {errors.name && <p className="mt-1 text-sm text-error">{errors.name}</p>}
                            </div>
                            <div>
                                <Label>Avatar</Label>
                                <Input
                                    type="file"
                                    accept="image/*"
                                    className="mt-1"
                                    onChange={(e) => setData('avatar', e.target.files?.[0] ?? null)}
                                />
                            </div>
                            <div>
                                <Label>Model AI *</Label>
                                <Input
                                    value={data.model}
                                    onChange={(e) => setData('model', e.target.value)}
                                    className="mt-1"
                                    required
                                />
                            </div>
                            <div>
                                <Label>Bahasa</Label>
                                <select
                                    value={data.language}
                                    onChange={(e) => setData('language', e.target.value)}
                                    className="mt-1 flex h-10 w-full rounded-md border border-hairline px-3 text-sm"
                                >
                                    <option value="id">Indonesia</option>
                                    <option value="en">English</option>
                                </select>
                            </div>
                            <div>
                                <Label>Temperature</Label>
                                <Input
                                    type="number"
                                    step="0.1"
                                    min="0"
                                    max="1"
                                    value={data.temperature}
                                    onChange={(e) => setData('temperature', e.target.value)}
                                    className="mt-1"
                                />
                            </div>
                            <div>
                                <Label>Max Context</Label>
                                <Input
                                    type="number"
                                    min="1"
                                    max="50"
                                    value={data.max_context}
                                    onChange={(e) => setData('max_context', e.target.value)}
                                    className="mt-1"
                                />
                            </div>
                        </div>
                        <div>
                            <Label>System Prompt</Label>
                            <textarea
                                rows={4}
                                value={data.system_prompt}
                                onChange={(e) => setData('system_prompt', e.target.value)}
                                className="mt-1 w-full rounded-md border border-hairline px-3 py-2 text-sm"
                            />
                        </div>
                        <div>
                            <Label>Pesan Fallback</Label>
                            <Input
                                value={data.fallback_message}
                                onChange={(e) => setData('fallback_message', e.target.value)}
                                className="mt-1"
                            />
                        </div>
                        <div>
                            <Label>Kata Kunci Handoff (satu per baris)</Label>
                            <textarea
                                rows={3}
                                value={data.handoff_triggers}
                                onChange={(e) => setData('handoff_triggers', e.target.value)}
                                className="mt-1 w-full rounded-md border border-hairline px-3 py-2 text-sm"
                            />
                        </div>
                        <label className="flex items-center gap-2 text-sm">
                            <input
                                type="checkbox"
                                checked={data.is_active}
                                onChange={(e) => setData('is_active', e.target.checked)}
                            />
                            Chatbot aktif
                        </label>
                        <Button type="submit" disabled={processing}>
                            Simpan Chatbot
                        </Button>
                    </form>
                </div>
            </div>
        </Layout>
    );
}
