import { cn } from '@/lib/utils';
import { ReactNode } from 'react';
import { Spotlight } from '@/components/ui/spotlight';

interface SectionShellProps {
    id: string;
    children: ReactNode;
    className?: string;
    withSpotlight?: boolean;
    border?: boolean;
}

export function SectionShell({
    id,
    children,
    className,
    withSpotlight = false,
    border = false,
}: SectionShellProps) {
    return (
        <section
            id={id}
            className={cn(
                'relative overflow-hidden py-20 sm:py-32',
                border && 'border-y border-hairline',
                className,
            )}
        >
            {withSpotlight && (
                <div className="pointer-events-none absolute inset-0 overflow-hidden">
                    <Spotlight className="-top-40 left-0 md:-top-20 md:left-60" fill="white" />
                </div>
            )}
            <div className="relative mx-auto max-w-6xl px-4 sm:px-6">{children}</div>
        </section>
    );
}
