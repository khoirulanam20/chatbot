import { useCallback, useEffect, useRef, useState } from 'react';
import { router } from '@inertiajs/react';
import { Loader2, RefreshCw, Smartphone } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { StatusBadge } from '@/components/StatusBadge';
import type { WaInstance } from '@/types';

interface ConnectionStatusResponse {
    success: boolean;
    status?: string;
    phone?: string | null;
    is_connected?: boolean;
    local_status?: string;
    phone_number?: string | null;
    error?: string;
}

interface Props {
    waInstance: WaInstance;
    hasApiKey: boolean;
    autoInitiate?: boolean;
    onConnected?: () => void;
}

export function WaQrConnect({ waInstance, hasApiKey, autoInitiate = false, onConnected }: Props) {
    const [qrKey, setQrKey] = useState(Date.now());
    const [chateryStatus, setChateryStatus] = useState<string | null>(
        waInstance.metadata?.chatery_status ?? null
    );
    const [localStatus, setLocalStatus] = useState(waInstance.status);
    const [phoneNumber, setPhoneNumber] = useState<string | null>(waInstance.phone_number ?? null);
    const [error, setError] = useState<string | null>(waInstance.metadata?.last_error ?? null);
    const [initiating, setInitiating] = useState(false);
    const [polling, setPolling] = useState(false);
    const connectedRef = useRef(false);
    const autoInitiatedRef = useRef(false);

    const refreshQr = useCallback(() => {
        setQrKey(Date.now());
    }, []);

    const pollStatus = useCallback(async () => {
        if (connectedRef.current) {
            return;
        }

        setPolling(true);
        try {
            const res = await fetch(`/admin/wa/${waInstance.id}/status`, {
                headers: { Accept: 'application/json' },
            });
            const data: ConnectionStatusResponse = await res.json();

            if (!data.success) {
                setError(data.error ?? 'Gagal memuat status koneksi.');
                return;
            }

            setError(null);
            setChateryStatus(data.status ?? null);
            setLocalStatus(data.local_status ?? waInstance.status);
            setPhoneNumber(data.phone_number ?? data.phone ?? null);

            if (data.is_connected) {
                connectedRef.current = true;
                onConnected?.();
            }
        } catch {
            setError('Gagal menghubungi server.');
        } finally {
            setPolling(false);
        }
    }, [onConnected, waInstance.id, waInstance.status]);

    const initiateConnect = useCallback(() => {
        setInitiating(true);
        setError(null);

        router.post(
            `/admin/wa/${waInstance.id}/connect`,
            {},
            {
                preserveScroll: true,
                onFinish: () => {
                    setInitiating(false);
                    refreshQr();
                    void pollStatus();
                },
            }
        );
    }, [pollStatus, refreshQr, waInstance.id]);

    useEffect(() => {
        if (!hasApiKey || connectedRef.current || localStatus === 'active') {
            return;
        }

        if (autoInitiate && !autoInitiatedRef.current) {
            autoInitiatedRef.current = true;
            initiateConnect();
            return;
        }

        void pollStatus();
    }, [autoInitiate, hasApiKey, initiateConnect, localStatus, pollStatus]);

    useEffect(() => {
        if (!hasApiKey || connectedRef.current || localStatus === 'active') {
            return;
        }

        const statusInterval = window.setInterval(() => {
            void pollStatus();
        }, 3000);

        const qrInterval = window.setInterval(() => {
            refreshQr();
        }, 15000);

        return () => {
            window.clearInterval(statusInterval);
            window.clearInterval(qrInterval);
        };
    }, [hasApiKey, localStatus, pollStatus, refreshQr]);

    useEffect(() => {
        if (localStatus === 'active' && connectedRef.current) {
            const timeout = window.setTimeout(() => {
                router.visit('/admin/wa', {
                    preserveScroll: false,
                });
            }, 1500);

            return () => window.clearTimeout(timeout);
        }
    }, [localStatus]);

    const showQr = hasApiKey && localStatus !== 'active';

    return (
        <div className="space-y-4">
            <div className="flex flex-wrap items-center gap-2">
                <StatusBadge status={localStatus} />
                {chateryStatus && (
                    <span className="text-xs capitalize text-muted">Chatery: {chateryStatus.replace('_', ' ')}</span>
                )}
                {polling && <Loader2 className="h-4 w-4 animate-spin text-muted" />}
            </div>

            {waInstance.instance_id && (
                <p className="font-mono text-xs text-muted">Session ID: {waInstance.instance_id}</p>
            )}

            {!hasApiKey && (
                <div className="rounded-lg border border-error/30 bg-error/10 p-3 text-sm text-error">
                    CHATERY_API_KEY belum dikonfigurasi di .env.
                </div>
            )}

            {error && (
                <div className="rounded-lg border border-error/30 bg-error/10 p-3 text-sm text-error">
                    {error}
                </div>
            )}

            {localStatus === 'active' && phoneNumber && (
                <div className="rounded-lg border border-success/30 bg-success/10 p-4 text-sm">
                    <p className="font-medium">WhatsApp terhubung</p>
                    <p className="mt-1 font-mono">{phoneNumber}</p>
                    <p className="mt-2 text-xs text-muted">Mengalihkan ke daftar instance...</p>
                </div>
            )}

            {showQr && (
                <div className="flex flex-col items-center gap-4 rounded-lg border border-hairline bg-surface-soft p-6">
                    <div className="flex items-center gap-2 text-sm font-medium">
                        <Smartphone className="h-4 w-4" />
                        Scan QR dengan WhatsApp di HP
                    </div>
                    <div className="rounded-lg border border-hairline bg-white p-3">
                        <img
                            src={`/admin/wa/${waInstance.id}/qr?t=${qrKey}`}
                            alt="QR Code WhatsApp"
                            className="h-64 w-64 object-contain"
                            onError={refreshQr}
                        />
                    </div>
                    <p className="max-w-sm text-center text-xs text-muted">
                        Buka WhatsApp → Perangkat Tertaut → Tautkan perangkat. QR diperbarui otomatis setiap 15 detik.
                    </p>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        className="gap-2"
                        onClick={initiateConnect}
                        disabled={initiating || !hasApiKey}
                    >
                        {initiating ? (
                            <Loader2 className="h-4 w-4 animate-spin" />
                        ) : (
                            <RefreshCw className="h-4 w-4" />
                        )}
                        Hubungkan ulang
                    </Button>
                </div>
            )}
        </div>
    );
}
