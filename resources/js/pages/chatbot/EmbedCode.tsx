import { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import { Layout } from '@/components/Layout';
import { Button } from '@/components/ui/button';
import type { Chatbot } from '@/types';

interface Props {
    chatbot: Chatbot;
}

export default function EmbedCode({ chatbot }: Props) {
    const origin = typeof window !== 'undefined' ? window.location.origin : '';
    const embedCode = `<!-- AI CS Chatbot Widget -->
<script
  src="${origin}/chatbot.js"
  data-bot-id="${chatbot.id}"
  defer></script>`;

    const [copied, setCopied] = useState(false);

    const copy = async () => {
        await navigator.clipboard.writeText(embedCode);
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
    };

    return (
        <Layout>
            <Head title={`Embed — ${chatbot.name}`} />
            <div className="mx-auto max-w-3xl space-y-6">
                <Link href={`/admin/chatbot/${chatbot.id}/edit`} className="text-sm text-muted hover:text-ink">
                    ← Kembali
                </Link>
                <div className="space-y-6 rounded-lg border border-hairline bg-surface-card p-6">
                    <div>
                        <h1 className="font-display text-lg font-semibold">Embed Code untuk {chatbot.name}</h1>
                        <p className="mt-1 text-sm text-muted">Tambahkan sebelum tag &lt;/body&gt;</p>
                    </div>
                    <pre className="overflow-x-auto rounded-xl bg-surface-dark p-5 text-sm text-green-400">{embedCode}</pre>
                    <Button onClick={copy}>{copied ? 'Tersalin!' : 'Salin Kode'}</Button>
                    <div className="grid gap-3 border-t border-hairline pt-5 text-sm md:grid-cols-2">
                        <div className="rounded-lg bg-surface-soft p-3">
                            <p className="text-xs text-muted">Bot ID</p>
                            <p className="font-mono font-medium">{chatbot.id}</p>
                        </div>
                        <div className="rounded-lg bg-surface-soft p-3">
                            <p className="text-xs text-muted">Widget URL</p>
                            <p className="break-all font-mono text-xs">{origin}/chatbot.js</p>
                        </div>
                    </div>
                </div>
            </div>
        </Layout>
    );
}
