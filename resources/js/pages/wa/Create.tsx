import { FormEventHandler } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { Layout } from '@/components/Layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Chatbot } from '@/types';

interface Props {
    chatbots: Chatbot[];
    hasApiKey: boolean;
}

export default function WaCreate({ chatbots, hasApiKey }: Props) {
    const { data, setData, post, processing } = useForm({
        chatbot_id: String(chatbots[0]?.id ?? ''),
        typing_enabled: false,
        typing_duration_ms: '2000',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/admin/wa');
    };

    return (
        <Layout>
            <Head title="Tambah WA Instance" />
            <div className="mx-auto max-w-lg space-y-6">
                <Link href="/admin/wa" className="text-sm text-muted">
                    ← Kembali
                </Link>
                <form onSubmit={submit} className="space-y-4 rounded-lg border border-hairline bg-surface-card p-6">
                    <h1 className="font-display text-lg font-semibold">Tambah WA Instance</h1>
                    <p className="text-sm text-muted">
                        Pilih chatbot, lalu scan QR code langsung dari halaman berikutnya. Webhook didaftarkan otomatis.
                    </p>

                    {!hasApiKey && (
                        <div className="rounded-lg border border-error/30 bg-error/10 p-3 text-sm text-error">
                            CHATERY_API_KEY belum dikonfigurasi di .env. Hubungi administrator server.
                        </div>
                    )}

                    {chatbots.length === 0 ? (
                        <div className="rounded-lg border border-hairline bg-surface-soft p-4 text-sm text-muted">
                            Semua chatbot sudah memiliki instance WhatsApp, atau belum ada chatbot.
                        </div>
                    ) : (
                        <>
                            <div>
                                <Label>Chatbot *</Label>
                                <select
                                    value={data.chatbot_id}
                                    onChange={(e) => setData('chatbot_id', e.target.value)}
                                    className="mt-1 flex h-10 w-full rounded-md border border-hairline px-3 text-sm"
                                    required
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
                                <p className="text-xs text-muted">
                                    Menampilkan status &quot;sedang mengetik&quot; di WhatsApp sebelum balasan terkirim.
                                </p>
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
                                        <p className="mt-1 text-xs text-muted">Default 2000 ms (2 detik).</p>
                                    </div>
                                )}
                            </div>
                            <Button type="submit" disabled={processing || !hasApiKey || chatbots.length === 0}>
                                Lanjut — Hubungkan WhatsApp
                            </Button>
                        </>
                    )}
                </form>
            </div>
        </Layout>
    );
}
