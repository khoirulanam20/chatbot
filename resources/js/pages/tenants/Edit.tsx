import { FormEventHandler } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { Layout } from '@/components/Layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { TenantFull } from '@/types';

interface Props {
    tenant: TenantFull;
}

export default function TenantsEdit({ tenant }: Props) {
    const { data, setData, put, processing } = useForm({
        name: tenant.name,
        plan: tenant.plan ?? 'free',
        is_active: tenant.is_active ?? true,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        put(`/admin/tenants/${tenant.id}`);
    };

    return (
        <Layout>
            <Head title={`Edit — ${tenant.name}`} />
            <div className="mx-auto max-w-lg space-y-6">
                <Link href="/admin/tenants" className="text-sm text-muted">← Kembali</Link>
                <form onSubmit={submit} className="space-y-4 rounded-lg border border-hairline bg-surface-card p-6">
                    <h1 className="font-display text-lg font-semibold">Edit Tenant</h1>
                    <p className="text-sm text-muted">{tenant.users_count} pengguna · {tenant.chatbots_count} chatbot</p>
                    <div><Label>Nama *</Label><Input value={data.name} onChange={(e) => setData('name', e.target.value)} className="mt-1" required /></div>
                    <div>
                        <Label>Plan</Label>
                        <select value={data.plan} onChange={(e) => setData('plan', e.target.value)} className="mt-1 flex h-10 w-full rounded-md border border-hairline px-3 text-sm">
                            <option value="free">Free</option>
                            <option value="pro">Pro</option>
                            <option value="enterprise">Enterprise</option>
                        </select>
                    </div>
                    <label className="flex items-center gap-2 text-sm">
                        <input type="checkbox" checked={data.is_active} onChange={(e) => setData('is_active', e.target.checked)} />
                        Tenant aktif
                    </label>
                    <Button type="submit" disabled={processing}>Simpan</Button>
                </form>
            </div>
        </Layout>
    );
}
