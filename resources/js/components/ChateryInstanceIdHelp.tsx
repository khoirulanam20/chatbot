import { useState } from 'react';
import { Loader2, RefreshCw } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';

interface ChaterySession {
    id: string;
    phone?: string | null;
    status?: string | null;
    name?: string | null;
}

interface Props {
    apiKey: string;
    instanceId: string;
    onSelectSession: (session: ChaterySession) => void;
}

export function ChateryInstanceIdHelp({ apiKey, instanceId, onSelectSession }: Props) {
    const [loading, setLoading] = useState(false);
    const [sessions, setSessions] = useState<ChaterySession[]>([]);
    const [fetchError, setFetchError] = useState<string | null>(null);

    const loadSessions = async () => {
        if (!apiKey.trim()) {
            setFetchError('Isi API Key terlebih dahulu.');
            return;
        }
        setLoading(true);
        setFetchError(null);
        try {
            const res = await fetch('/admin/wa/preview-sessions', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN':
                        document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '',
                },
                body: JSON.stringify({ api_key: apiKey }),
            });
            const data = await res.json();
            if (!data.success) {
                setFetchError(data.error ?? 'Gagal memuat daftar sesi.');
                setSessions([]);
                return;
            }
            setSessions(data.sessions ?? []);
            if ((data.sessions ?? []).length === 0) {
                setFetchError('Tidak ada sesi. Buat sesi baru di wa.firstudio.id/dashboard.');
            }
        } catch {
            setFetchError('Gagal menghubungi server.');
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="space-y-3 rounded-lg border border-hairline bg-surface-soft p-4 text-sm">
            <div>
                <p className="font-medium">Instance ID (sessionId Chatery)</p>
                <p className="mt-1 text-xs text-muted">
                    Bukan dibuat otomatis di sini. Ambil dari dashboard Chatery — biasanya field{' '}
                    <code className="rounded bg-canvas px-1">id</code> atau{' '}
                    <code className="rounded bg-canvas px-1">sessionId</code> di response API{' '}
                    <strong>GET /sessions</strong>. Kosong = memakai <code className="rounded bg-canvas px-1">default</code>.
                </p>
            </div>
            <Button type="button" variant="outline" size="sm" className="gap-2" onClick={loadSessions} disabled={loading}>
                {loading ? <Loader2 className="h-4 w-4 animate-spin" /> : <RefreshCw className="h-4 w-4" />}
                Muat sesi dari Chatery
            </Button>
            {fetchError && <p className="text-xs text-error">{fetchError}</p>}
            {sessions.length > 0 && (
                <div className="space-y-2">
                    <Label className="text-xs text-muted">Klik sesi untuk isi Instance ID & nomor</Label>
                    <ul className="max-h-40 space-y-1 overflow-y-auto">
                        {sessions.map((s) => (
                            <li key={s.id}>
                                <button
                                    type="button"
                                    onClick={() => onSelectSession(s)}
                                    className={`w-full rounded-md border px-3 py-2 text-left text-xs transition-colors hover:bg-canvas ${
                                        instanceId === s.id ? 'border-primary bg-canvas' : 'border-hairline'
                                    }`}
                                >
                                    <span className="font-mono font-medium">{s.id}</span>
                                    {s.phone && <span className="ml-2 text-muted">{s.phone}</span>}
                                    {s.status && (
                                        <span className="ml-2 capitalize text-muted">· {s.status}</span>
                                    )}
                                </button>
                            </li>
                        ))}
                    </ul>
                </div>
            )}
        </div>
    );
}
