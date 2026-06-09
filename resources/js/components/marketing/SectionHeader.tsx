import { ReactNode } from 'react';
import { AnimateInView } from './motion/AnimateInView';

interface SectionHeaderProps {
    eyebrow?: string;
    headline: string;
    subheadline?: string;
    align?: 'left' | 'center';
    className?: string;
    pill?: boolean;
}

export function SectionHeader({
    eyebrow,
    headline,
    subheadline,
    align = 'center',
    className = '',
    pill = false,
}: SectionHeaderProps) {
    return (
        <AnimateInView className={`mx-auto max-w-2xl ${align === 'center' ? 'text-center' : 'text-left'} ${className}`}>
            {eyebrow && (
                pill ? (
                    <span className="mb-4 inline-block rounded-pill border border-hairline bg-canvas px-3 py-1 text-xs font-semibold tracking-widest text-muted uppercase shadow-sm">
                        {eyebrow}
                    </span>
                ) : (
                    <span className="mb-3 block text-sm font-medium text-primary">
                        {eyebrow}
                    </span>
                )
            )}
            <h2 className="font-display text-3xl font-bold text-ink sm:text-4xl">
                {headline}
            </h2>
            {subheadline && (
                <p className="mt-4 text-lg text-muted">
                    {subheadline}
                </p>
            )}
        </AnimateInView>
    );
}
