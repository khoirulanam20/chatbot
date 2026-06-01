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

export default function UsersCreate({ tenants }: Props) {
    const { auth } = usePage<PageProps>().props;
    const isSuperAdmin = auth.user?.role === 'super_admin';

    const { data, setData, post, processing, errors } = useForm({
        tenant_id: isSuperAdmin ? '' : String(auth.user?.tenant_id ?? ''),
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        role: 'operator',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/admin/users');
    };

    return (
        <Layout>
            <Head title="Tambah Pengguna" />
            <div className="mx-auto max-w-lg space-y-6">
                <Link href="/admin/users" className="text-sm text-muted">← Kembali</Link>
                <form onSubmit={submit} className="space-y-4 rounded-lg border border-hairline bg-surface-card p-6">
                    <h1 className="font-display text-lg font-semibold">Tambah Pengguna</h1>
                    {isSuperAdmin && (
                        <div>
                            <Label>Tenant</Label>
                            <select value={data.tenant_id} onChange={(e) => setData('tenant_id', e.target.value)} className="mt-1 flex h-10 w-full rounded-md border border-hairline px-3 text-sm">
                                <option value="">—</option>
                                {tenants.map((t) => <option key={t.id} value={t.id}>{t.name}</option>)}
                            </select>
                        </div>
                    )}
                    <div><Label>Nama *</Label><Input value={data.name} onChange={(e) => setData('name', e.target.value)} className="mt-1" required />{errors.name && <p className="text-sm text-error">{errors.name}</p>}</div>
                    <div><Label>Email *</Label><Input type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} className="mt-1" required /></div>
                    <div><Label>Password *</Label><Input type="password" value={data.password} onChange={(e) => setData('password', e.target.value)} className="mt-1" required /></div>
                    <div><Label>Konfirmasi Password *</Label><Input type="password" value={data.password_confirmation} onChange={(e) => setData('password_confirmation', e.target.value)} className="mt-1" required /></div>
                    <div>
                        <Label>Role *</Label>
                        <select value={data.role} onChange={(e) => setData('role', e.target.value)} className="mt-1 flex h-10 w-full rounded-md border border-hairline px-3 text-sm">
                            {isSuperAdmin && <option value="super_admin">Super Admin</option>}
                            <option value="admin">Admin</option>
                            <option value="operator">Operator</option>
                            <option value="viewer">Viewer</option>
                        </select>
                    </div>
                    <Button type="submit" disabled={processing}>Simpan</Button>
                </form>
            </div>
        </Layout>
    );
}
