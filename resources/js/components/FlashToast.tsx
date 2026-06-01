import { useEffect } from 'react';
import { usePage } from '@inertiajs/react';
import type { PageProps } from '@/types';

export function FlashToast() {
    const { flash } = usePage<PageProps>().props;

    useEffect(() => {
        if (flash.success) {
            // eslint-disable-next-line no-alert
            // Optional: could use sonner later
        }
    }, [flash.success, flash.error]);

    if (!flash.success && !flash.error) return null;

    return (
        <div className="fixed top-4 right-4 z-50 max-w-sm">
            {flash.success && (
                <div className="rounded-lg border border-success/30 bg-success/10 px-4 py-3 text-sm text-success">
                    {flash.success}
                </div>
            )}
            {flash.error && (
                <div className="mt-2 rounded-lg border border-error/30 bg-error/10 px-4 py-3 text-sm text-error">
                    {flash.error}
                </div>
            )}
        </div>
    );
}
