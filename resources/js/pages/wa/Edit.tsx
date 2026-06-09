import { FormEventHandler } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { Layout } from '@/components/Layout';
import { WaQrConnect } from '@/components/WaQrConnect';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Chatbot, WaInstance } from '@/types';

interface Props {
    waInstance: WaInstance;
    chatbots: Chatbot[];
    hasApiKey: boolean;
}

export default function WaEdit({ waInstance, chatbots, hasApiKey }: Props) {
    const { data, setData, put, processing } = useForm({
        chatbot_id: String(waInstance.chatbot_id),
        typing_enabled: waInstance.typing_enabled ?? false,
        typing_duration_ms: String(waInstance.typing_duration_ms ?? 2000),
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        put(`/admin/wa/${waInstance.id}`);
    };

    const showConnect = waInstance.status !== 'active';

    return (
        <Layout>
            <Head title="Edit WA Instance" />
            <div className="mx-auto max-w-lg space-y-6">
                <Link href="/admin/wa" className="text-sm text-muted">
                    ← Kembali
                </Link>
                <form onSubmit={submit} className="space-y-4 rounded-lg border border-hairline bg-surface-card p-6">
                    <h1 className="font-display text-lg font-semibold">Edit WA Instance</h1>

                    {waInstance.phone_number && (
                        <div>
                            <Label>Nomor WhatsApp</Label>
                            <p className="mt-1 font-mono text-sm">{waInstance.phone_number}</p>
                        </div>
                    )}

                    {waInstance.instance_id && (
                        <div>
                            <Label>Session ID</Label>
                            <p className="mt-1 font-mono text-sm">{waInstance.instance_id}</p>
                        </div>
                    )}

                    {waInstance.status === 'error' && waInstance.metadata?.last_error && (
                        <div className="rounded-lg border border-error/30 bg-error/10 p-3 text-sm text-error">
                            <p className="font-medium">Error koneksi terakhir</p>
                            <p className="mt-1 text-xs">{waInstance.metadata.last_error}</p>
                        </div>
                    )}

                    <div>
                        <Label>Chatbot *</Label>
                        <select
                            value={data.chatbot_id}
                            onChange={(e) => setData('chatbot_id', e.target.value)}
                            className="mt-1 flex h-10 w-full rounded-md border border-hairline px-3 text-sm"
                        >
                            {chatbots.map((b) => (
                                <option key={b.id} value={b.id}>
                                    {b.name}
                                </option>
                            ))}
                        </select>
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

                    {showConnect && (
                        <div className="rounded-lg border border-hairline bg-surface-soft p-4">
                            <p className="mb-3 text-sm font-medium">Koneksi WhatsApp</p>
                            <WaQrConnect waInstance={waInstance} hasApiKey={hasApiKey} />
                        </div>
                    )}

                    <div className="flex flex-col gap-2 sm:flex-row">
                        <Button
                            type="button"
                            variant="outline"
                            className="flex-1"
                            onClick={() => router.post(`/admin/wa/${waInstance.id}/test`)}
                        >
                            Tes koneksi
                        </Button>
                        {showConnect && (
                            <Button type="button" variant="outline" className="flex-1" asChild>
                                <Link href={`/admin/wa/${waInstance.id}/connect`}>Halaman QR</Link>
                            </Button>
                        )}
                    </div>

                    <Button type="submit" disabled={processing}>
                        Simpan
                    </Button>
                </form>
            </div>
        </Layout>
    );
}
