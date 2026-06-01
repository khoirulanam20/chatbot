import { FormEventHandler } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { Layout } from '@/components/Layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { User } from '@/types';

interface Props {
    user: User;
    tenants: unknown[];
}

export default function UsersEdit({ user }: Props) {
    const { data, setData, put, processing } = useForm({
        name: user.name,
        email: user.email,
        role: user.role,
        password: '',
        password_confirmation: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        put(`/admin/users/${user.id}`);
    };

    return (
        <Layout>
            <Head title={`Edit — ${user.name}`} />
            <div className="mx-auto max-w-lg space-y-6">
                <Link href="/admin/users" className="text-sm text-muted">← Kembali</Link>
                <form onSubmit={submit} className="space-y-4 rounded-lg border border-hairline bg-surface-card p-6">
                    <h1 className="font-display text-lg font-semibold">Edit Pengguna</h1>
                    <div><Label>Nama</Label><Input value={data.name} onChange={(e) => setData('name', e.target.value)} className="mt-1" required /></div>
                    <div><Label>Email</Label><Input type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} className="mt-1" required /></div>
                    <div><Label>Role</Label>
                        <select value={data.role} onChange={(e) => setData('role', e.target.value)} className="mt-1 flex h-10 w-full rounded-md border border-hairline px-3 text-sm">
                            <option value="admin">Admin</option>
                            <option value="operator">Operator</option>
                            <option value="viewer">Viewer</option>
                        </select>
                    </div>
                    <div><Label>Password Baru (kosongkan jika tidak diubah)</Label><Input type="password" value={data.password} onChange={(e) => setData('password', e.target.value)} className="mt-1" /></div>
                    <div><Label>Konfirmasi</Label><Input type="password" value={data.password_confirmation} onChange={(e) => setData('password_confirmation', e.target.value)} className="mt-1" /></div>
                    <Button type="submit" disabled={processing}>Simpan</Button>
                </form>
            </div>
        </Layout>
    );
}
