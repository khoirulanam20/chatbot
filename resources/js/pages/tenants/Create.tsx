import { FormEventHandler } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { Layout } from '@/components/Layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export default function TenantsCreate() {
    const { data, setData, post, processing } = useForm({
        name: '',
        slug: '',
        plan: 'free',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/admin/tenants');
    };

    return (
        <Layout>
            <Head title="Tambah Tenant" />
            <div className="mx-auto max-w-lg space-y-6">
                <Link href="/admin/tenants" className="text-sm text-muted">← Kembali</Link>
                <form onSubmit={submit} className="space-y-4 rounded-lg border border-hairline bg-surface-card p-6">
                    <h1 className="font-display text-lg font-semibold">Tambah Tenant</h1>
                    <div><Label>Nama *</Label><Input value={data.name} onChange={(e) => setData('name', e.target.value)} className="mt-1" required /></div>
                    <div><Label>Slug *</Label><Input value={data.slug} onChange={(e) => setData('slug', e.target.value)} className="mt-1" required /></div>
                    <div>
                        <Label>Plan *</Label>
                        <select value={data.plan} onChange={(e) => setData('plan', e.target.value)} className="mt-1 flex h-10 w-full rounded-md border border-hairline px-3 text-sm">
                            <option value="free">Free</option>
                            <option value="pro">Pro</option>
                            <option value="enterprise">Enterprise</option>
                        </select>
                    </div>
                    <Button type="submit" disabled={processing}>Simpan</Button>
                </form>
            </div>
        </Layout>
    );
}
