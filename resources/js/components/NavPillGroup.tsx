import { ReactNode } from 'react';
import { cn } from '@/lib/utils';

export function NavPillGroup({ children }: { children: ReactNode }) {
    return (
        <div className="max-w-full overflow-x-auto">
            <div className="inline-flex min-w-max items-center gap-1 rounded-pill bg-surface-soft p-1">{children}</div>
        </div>
    );
}

export function NavPillItem({
    active,
    onClick,
    children,
}: {
    active?: boolean;
    onClick: () => void;
    children: ReactNode;
}) {
    return (
        <button
            type="button"
            onClick={onClick}
            className={cn(
                'shrink-0 rounded-md px-4 py-2 text-sm font-medium transition-colors',
                active ? 'bg-canvas text-ink shadow-sm' : 'text-muted hover:text-ink'
            )}
        >
            {children}
        </button>
    );
}
