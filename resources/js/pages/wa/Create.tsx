import { FormEventHandler } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { Layout } from '@/components/Layout';
import { ChateryInstanceIdHelp } from '@/components/ChateryInstanceIdHelp';
import { WebhookUrlField } from '@/components/WebhookUrlField';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Chatbot } from '@/types';

interface Props {
    chatbots: Chatbot[];
    webhookUrl: string;
}

export default function WaCreate({ chatbots, webhookUrl }: Props) {
    const { data, setData, post, processing } = useForm({
        chatbot_id: String(chatbots[0]?.id ?? ''),
        phone_number: '',
        api_key: '',
        instance_id: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/admin/wa');
    };

    return (
        <Layout>
            <Head title="Tambah WA Instance" />
            <div className="mx-auto max-w-lg space-y-6">
                <Link href="/admin/wa" className="text-sm text-muted">← Kembali</Link>
                <form onSubmit={submit} className="space-y-4 rounded-lg border border-hairline bg-surface-card p-6">
                    <h1 className="font-display text-lg font-semibold">Tambah WA Instance</h1>
                    <WebhookUrlField url={webhookUrl} />
                    <div>
                        <Label>Chatbot *</Label>
                        <select value={data.chatbot_id} onChange={(e) => setData('chatbot_id', e.target.value)} className="mt-1 flex h-10 w-full rounded-md border border-hairline px-3 text-sm" required>
                            {chatbots.map((b) => <option key={b.id} value={b.id}>{b.name}</option>)}
                        </select>
                    </div>
                    <div>
                        <Label>API Key *</Label>
                        <Input value={data.api_key} onChange={(e) => setData('api_key', e.target.value)} className="mt-1" required />
                    </div>
                    <ChateryInstanceIdHelp
                        apiKey={data.api_key}
                        instanceId={data.instance_id}
                        onSelectSession={(s) => {
                            setData('instance_id', s.id);
                            if (s.phone) {
                                setData('phone_number', String(s.phone).replace(/\D/g, ''));
                            }
                        }}
                    />
                    <div>
                        <Label>Instance ID</Label>
                        <Input
                            value={data.instance_id}
                            onChange={(e) => setData('instance_id', e.target.value)}
                            className="mt-1 font-mono"
                            placeholder="sessionId dari Chatery (mis. nama sesi)"
                        />
                    </div>
                    <div>
                        <Label>Nomor Telepon *</Label>
                        <Input
                            value={data.phone_number}
                            onChange={(e) => setData('phone_number', e.target.value)}
                            className="mt-1"
                            placeholder="6285117742328"
                            required
                        />
                        <p className="mt-1 text-xs text-muted">Format internasional tanpa 0 di depan (sesuai tampilan di Chatery).</p>
                    </div>
                    <Button type="submit" disabled={processing}>Simpan</Button>
                </form>
            </div>
        </Layout>
    );
}
