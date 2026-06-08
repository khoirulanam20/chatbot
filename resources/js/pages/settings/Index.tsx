import { FormEventHandler, useState } from 'react';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { Layout } from '@/components/Layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { getCsrfToken } from '@/lib/csrf';
import type { PageProps, Tenant } from '@/types';

interface Props {
    global: Record<string, string>;
    tenantSettings: Record<string, string>;
    tenant: Tenant | null;
    tenants: Tenant[];
    isSuperAdmin: boolean;
}

export default function SettingsIndex({
    global,
    tenantSettings,
    tenant,
    tenants,
    isSuperAdmin,
}: Props) {
    const { props: pageProps } = usePage<PageProps>();
    const [testResult, setTestResult] = useState<string | null>(null);

    const tenantForm = useForm({
        tenant_id: tenant?.id ? String(tenant.id) : '',
        ai_api_key: tenantSettings.ai_api_key ?? '',
        ai_base_url: tenantSettings.ai_base_url ?? '',
        ai_embed_model: tenantSettings.ai_embed_model ?? '',
        ai_chat_model: tenantSettings.ai_chat_model ?? '',
    });

    const globalForm = useForm({
        sumopod_api_key: '',
        sumopod_base_url: global.ai_base_url ?? '',
        sumopod_embed_model: global.ai_embed_model ?? '',
        sumopod_chat_model: global.ai_chat_model ?? '',
        context_tenant_id: tenant?.id ? String(tenant.id) : '',
    });

    const submitTenant: FormEventHandler = (e) => {
        e.preventDefault();
        tenantForm.post('/admin/settings');
    };

    const submitGlobal: FormEventHandler = (e) => {
        e.preventDefault();
        globalForm.post('/admin/settings/global');
    };

    const testAI = async () => {
        setTestResult('Memeriksa...');
        const csrfToken = getCsrfToken(pageProps.csrf_token);
        if (!csrfToken) {
            setTestResult('CSRF token tidak tersedia. Muat ulang halaman lalu coba lagi.');
            return;
        }
        try {
            const res = await fetch('/admin/settings/test-ai', {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-XSRF-TOKEN': csrfToken,
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    _token: csrfToken,
                    tenant_id: tenant?.id ?? undefined,
                    ai_api_key: tenantForm.data.ai_api_key || undefined,
                    ai_base_url: tenantForm.data.ai_base_url || undefined,
                    ai_embed_model: tenantForm.data.ai_embed_model || undefined,
                    ai_chat_model: tenantForm.data.ai_chat_model || undefined,
                }),
            });
            if (res.status === 419) {
                setTestResult('Sesi kedaluwarsa. Muat ulang halaman lalu coba lagi.');
                return;
            }
            const data = await res.json();
            let message = data.message ?? (data.success ? 'Berhasil' : 'Gagal');
            if (data.config) {
                const suffix = ` [base: ${data.config.base_url}, chat: ${data.config.chat_model}, embed: ${data.config.embed_model}]`;
                message += suffix;
            }
            setTestResult(message);
        } catch {
            setTestResult('Gagal menghubungi server');
        }
    };

    const selectTenant = (id: string) => {
        router.get('/admin/settings', { tenant_id: id }, { preserveState: true });
    };

    return (
        <Layout>
            <Head title="Pengaturan AI" />
            <div className="mx-auto max-w-3xl space-y-6">
                <div>
                    <h1 className="font-display text-2xl font-semibold">Pengaturan AI</h1>
                    <p className="text-muted">Konfigurasi API key dan model per tenant</p>
                </div>

                {isSuperAdmin && tenants.length > 0 && (
                    <div className="rounded-lg border border-hairline bg-surface-card p-5">
                        <Label>Pilih Tenant</Label>
                        <select
                            value={String(tenant?.id ?? '')}
                            onChange={(e) => selectTenant(e.target.value)}
                            className="mt-2 flex h-10 w-full rounded-md border border-hairline px-3 text-sm"
                        >
                            {tenants.map((t) => (
                                <option key={t.id} value={t.id}>{t.name}</option>
                            ))}
                        </select>
                    </div>
                )}

                <form onSubmit={submitTenant} className="space-y-4 rounded-lg border border-hairline bg-surface-card p-6">
                    <h2 className="font-semibold">Pengaturan Tenant — {tenant?.name ?? 'Tenant'}</h2>
                    {isSuperAdmin && tenant && (
                        <input type="hidden" name="tenant_id" value={tenant.id} />
                    )}
                    <div>
                        <Label>API Key</Label>
                        <Input type="password" value={tenantForm.data.ai_api_key} onChange={(e) => tenantForm.setData('ai_api_key', e.target.value)} className="mt-1 font-mono" placeholder="Kosongkan = pakai global" />
                    </div>
                    <div>
                        <Label>Base URL</Label>
                        <Input type="url" value={tenantForm.data.ai_base_url} onChange={(e) => tenantForm.setData('ai_base_url', e.target.value)} className="mt-1 font-mono" placeholder={global.ai_base_url} />
                    </div>
                    <div className="grid gap-4 md:grid-cols-2">
                        <div>
                            <Label>Model Embedding</Label>
                            <Input value={tenantForm.data.ai_embed_model} onChange={(e) => tenantForm.setData('ai_embed_model', e.target.value)} className="mt-1 font-mono" placeholder={global.ai_embed_model || 'text-embedding-3-small'} />
                        </div>
                        <div>
                            <Label>Model Chat</Label>
                            <Input value={tenantForm.data.ai_chat_model} onChange={(e) => tenantForm.setData('ai_chat_model', e.target.value)} className="mt-1 font-mono" />
                        </div>
                    </div>
                    <div className="flex gap-2">
                        <Button type="submit" disabled={tenantForm.processing}>Simpan Pengaturan Tenant</Button>
                        <Button type="button" variant="outline" onClick={testAI}>Test Koneksi AI</Button>
                    </div>
                    <p className="text-xs text-muted">Test memakai nilai di form ini (belum perlu disimpan).</p>
                    {testResult && <p className="text-sm text-muted">{testResult}</p>}
                </form>

                {isSuperAdmin && (
                    <form onSubmit={submitGlobal} className="space-y-4 rounded-lg border border-hairline bg-surface-card p-6">
                        <h2 className="font-semibold">Default Global (.env)</h2>
                        <div>
                            <Label>API Key Global</Label>
                            <Input type="password" value={globalForm.data.sumopod_api_key} onChange={(e) => globalForm.setData('sumopod_api_key', e.target.value)} className="mt-1" placeholder="Kosongkan jika tidak diubah" />
                        </div>
                        <div>
                            <Label>Base URL *</Label>
                            <Input type="url" value={globalForm.data.sumopod_base_url} onChange={(e) => globalForm.setData('sumopod_base_url', e.target.value)} className="mt-1" required />
                        </div>
                        <div className="grid gap-4 md:grid-cols-2">
                            <div>
                                <Label>Embed Model *</Label>
                                <Input value={globalForm.data.sumopod_embed_model} onChange={(e) => globalForm.setData('sumopod_embed_model', e.target.value)} className="mt-1" required />
                            </div>
                            <div>
                                <Label>Chat Model *</Label>
                                <Input value={globalForm.data.sumopod_chat_model} onChange={(e) => globalForm.setData('sumopod_chat_model', e.target.value)} className="mt-1" required />
                            </div>
                        </div>
                        <Button type="submit" disabled={globalForm.processing}>Simpan Global Default</Button>
                    </form>
                )}
            </div>
        </Layout>
    );
}
