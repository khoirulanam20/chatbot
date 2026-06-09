import { Head, Link } from '@inertiajs/react';
import { Layout } from '@/components/Layout';
import { WaQrConnect } from '@/components/WaQrConnect';
import type { WaInstance } from '@/types';

interface ConnectionStatus {
    success?: boolean;
    status?: string | null;
    phone?: string | null;
    is_connected?: boolean;
}

interface Props {
    waInstance: WaInstance;
    hasApiKey: boolean;
    connectionStatus?: ConnectionStatus | null;
}

export default function WaConnect({ waInstance, hasApiKey }: Props) {
    return (
        <Layout>
            <Head title="Hubungkan WhatsApp" />
            <div className="mx-auto max-w-lg space-y-6">
                <Link href="/admin/wa" className="text-sm text-muted">
                    ← Kembali
                </Link>
                <div className="space-y-4 rounded-lg border border-hairline bg-surface-card p-6">
                    <div>
                        <h1 className="font-display text-lg font-semibold">Hubungkan WhatsApp</h1>
                        <p className="mt-1 text-sm text-muted">
                            {waInstance.chatbot?.name ?? 'Chatbot'} — scan QR untuk menyelesaikan koneksi.
                        </p>
                    </div>
                    <WaQrConnect waInstance={waInstance} hasApiKey={hasApiKey} autoInitiate />
                </div>
            </div>
        </Layout>
    );
}
