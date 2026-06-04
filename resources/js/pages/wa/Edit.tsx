import { FormEventHandler } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { Layout } from '@/components/Layout';
import { WebhookUrlField } from '@/components/WebhookUrlField';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Chatbot, WaInstance } from '@/types';

interface Props {
    waInstance: WaInstance;
    chatbots: Chatbot[];
    webhookUrl: string;
}

export default function WaEdit({ waInstance, chatbots, webhookUrl }: Props) {
    const { data, setData, put, processing } = useForm({
        chatbot_id: String(waInstance.chatbot_id),
        phone_number: waInstance.phone_number,
        api_key: '',
        instance_id: (waInstance as WaInstance & { instance_id?: string }).instance_id ?? '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        put(`/admin/wa/${waInstance.id}`);
    };

    return (
        <Layout>
            <Head title="Edit WA Instance" />
            <div className="mx-auto max-w-lg space-y-6">
                <Link href="/admin/wa" className="text-sm text-muted">← Kembali</Link>
                <form onSubmit={submit} className="space-y-4 rounded-lg border border-hairline bg-surface-card p-6">
                    <h1 className="font-display text-lg font-semibold">Edit WA Instance</h1>
                    <WebhookUrlField url={webhookUrl} />
                    <div>
                        <Label>Chatbot *</Label>
                        <select value={data.chatbot_id} onChange={(e) => setData('chatbot_id', e.target.value)} className="mt-1 flex h-10 w-full rounded-md border border-hairline px-3 text-sm">
                            {chatbots.map((b) => <option key={b.id} value={b.id}>{b.name}</option>)}
                        </select>
                    </div>
                    <div><Label>Nomor Telepon *</Label><Input value={data.phone_number} onChange={(e) => setData('phone_number', e.target.value)} className="mt-1" required /></div>
                    <div><Label>API Key (kosongkan jika tidak diubah)</Label><Input value={data.api_key} onChange={(e) => setData('api_key', e.target.value)} className="mt-1" /></div>
                    <div><Label>Instance ID</Label><Input value={data.instance_id} onChange={(e) => setData('instance_id', e.target.value)} className="mt-1" /></div>
                    <Button type="submit" disabled={processing}>Simpan</Button>
                </form>
            </div>
        </Layout>
    );
}
