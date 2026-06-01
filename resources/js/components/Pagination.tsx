import { Link } from '@inertiajs/react';
import type { Paginated } from '@/types';
import { cn } from '@/lib/utils';

export function Pagination<T>({ data }: { data: Paginated<T> }) {
    if (data.last_page <= 1) return null;

    return (
        <div className="mt-6 flex justify-center gap-2">
            {data.links.map((link, i) => (
                <Link
                    key={i}
                    href={link.url || '#'}
                    preserveScroll
                    className={cn(
                        'rounded-md px-3 py-2 text-sm',
                        link.active
                            ? 'bg-primary text-on-primary'
                            : 'bg-surface-card text-ink hover:bg-surface-soft',
                        !link.url && 'pointer-events-none opacity-50'
                    )}
                    dangerouslySetInnerHTML={{ __html: link.label }}
                />
            ))}
        </div>
    );
}
