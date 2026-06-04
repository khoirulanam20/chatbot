import { useState } from 'react';
import { Check, Copy } from 'lucide-react';
import { Button } from '@/components/ui/button';

interface Props {
    url: string;
}

export function WebhookUrlField({ url }: Props) {
    const [copied, setCopied] = useState(false);

    const copy = async () => {
        await navigator.clipboard.writeText(url);
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
    };

    return (
        <div className="rounded-lg border border-hairline bg-surface-soft p-4">
            <p className="text-sm font-medium">Webhook URL</p>
            <p className="mt-1 text-xs text-muted">
                Salin URL ini ke pengaturan webhook di panel WA Chatery
            </p>
            <div className="mt-3 flex flex-col gap-2 sm:flex-row sm:items-stretch">
                <code className="flex-1 break-all rounded-md border border-hairline bg-canvas px-3 py-2 font-mono text-xs leading-relaxed">
                    {url}
                </code>
                <Button type="button" variant="outline" size="sm" className="shrink-0 gap-2" onClick={copy}>
                    {copied ? <Check className="h-4 w-4" /> : <Copy className="h-4 w-4" />}
                    {copied ? 'Tersalin' : 'Salin URL'}
                </Button>
            </div>
        </div>
    );
}
