import { FormEventHandler } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { Layout } from '@/components/Layout';
import { ChateryInstanceIdHelp } from '@/components/ChateryInstanceIdHelp';
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
        instance_id: waInstance.instance_id ?? '',
        typing_enabled: waInstance.typing_enabled ?? false,
        typing_duration_ms: String(waInstance.typing_duration_ms ?? 2000),
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
                    {waInstance.status === 'error' && waInstance.metadata?.last_error && (
                        <div className="rounded-lg border border-error/30 bg-error/10 p-3 text-sm text-error">
                            <p className="font-medium">Error koneksi terakhir</p>
                            <p className="mt-1 text-xs">{waInstance.metadata.last_error}</p>
                        </div>
                    )}
                    <div>
                        <Label>Chatbot *</Label>
                        <select value={data.chatbot_id} onChange={(e) => setData('chatbot_id', e.target.value)} className="mt-1 flex h-10 w-full rounded-md border border-hairline px-3 text-sm">
                            {chatbots.map((b) => <option key={b.id} value={b.id}>{b.name}</option>)}
                        </select>
                    </div>
                    <div>
                        <Label>API Key (kosongkan jika tidak diubah)</Label>
                        <Input value={data.api_key} onChange={(e) => setData('api_key', e.target.value)} className="mt-1" />
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
                        />
                    </div>
                    <div>
                        <Label>Nomor Telepon *</Label>
                        <Input value={data.phone_number} onChange={(e) => setData('phone_number', e.target.value)} className="mt-1" required />
                    </div>
                    <div className="space-y-3 rounded-lg border border-hairline bg-surface-soft p-4">
                        <label className="flex items-center gap-2 text-sm font-medium">
                            <input
                                type="checkbox"
                                checked={data.typing_enabled}
                                onChange={(e) => setData('typing_enabled', e.target.checked)}
                            />
                            Aktifkan indikator mengetik
                        </label>
                        {data.typing_enabled && (
                            <div>
                                <Label>Durasi typing (ms)</Label>
                                <Input
                                    type="number"
                                    min={500}
                                    max={10000}
                                    step={500}
                                    value={data.typing_duration_ms}
                                    onChange={(e) => setData('typing_duration_ms', e.target.value)}
                                    className="mt-1"
                                />
                            </div>
                        )}
                    </div>
                    <Button
                        type="button"
                        variant="outline"
                        className="w-full"
                        onClick={() => {
                            router.post(`/admin/wa/${waInstance.id}/test`);
                        }}
                    >
                        Tes koneksi ke Chatery
                    </Button>
                    <Button type="submit" disabled={processing}>Simpan</Button>
                </form>
            </div>
        </Layout>
    );
}
