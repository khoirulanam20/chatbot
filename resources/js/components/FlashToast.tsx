import { usePage } from '@inertiajs/react';
import type { PageProps } from '@/types';

export function FlashToast() {
    const { flash, errors } = usePage<PageProps & { errors?: Record<string, string> }>().props;
    const connectionError = errors?.connection;

    if (!flash.success && !flash.error && !connectionError) return null;

    return (
        <div className="fixed top-4 right-4 z-50 max-w-sm">
            {flash.success && (
                <div className="rounded-lg border border-success/30 bg-success/10 px-4 py-3 text-sm text-success">
                    {flash.success}
                </div>
            )}
            {(flash.error || connectionError) && (
                <div className="mt-2 rounded-lg border border-error/30 bg-error/10 px-4 py-3 text-sm text-error">
                    {flash.error || connectionError}
                </div>
            )}
        </div>
    );
}
