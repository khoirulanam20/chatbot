import { cn } from '@/lib/utils';
import { AnimateInView } from '../motion/AnimateInView';
import { TypewriterEffect } from '@/components/ui/typewriter-effect';

interface AnimatedSectionHeaderProps {
    eyebrow?: string;
    headline: string;
    subheadline?: string;
    align?: 'left' | 'center';
    className?: string;
    typewriter?: boolean;
}

export function AnimatedSectionHeader({
    eyebrow,
    headline,
    subheadline,
    align = 'center',
    className,
    typewriter = false,
}: AnimatedSectionHeaderProps) {
    const alignClass = align === 'center' ? 'text-center mx-auto' : 'text-left';

    const headlineWords = headline.split(' ').map((word, i) => ({
        text: word,
        className: i === headline.split(' ').length - 1 ? 'text-primary' : 'text-on-dark',
    }));

    return (
        <AnimateInView className={cn('max-w-3xl', alignClass, className)}>
            {eyebrow && (
                <span className="mb-4 inline-block rounded-pill border border-white/[0.1] bg-surface-dark-elevated px-3 py-1 text-xs font-semibold tracking-widest text-muted uppercase">
                    {eyebrow}
                </span>
            )}
            {typewriter ? (
                <TypewriterEffect
                    words={headlineWords}
                    className={cn('font-display', align === 'left' ? 'text-left' : 'text-center')}
                    cursorClassName="bg-primary"
                />
            ) : (
                <h2 className="font-display text-3xl font-bold text-on-dark sm:text-4xl">{headline}</h2>
            )}
            {subheadline && (
                <p className="mt-4 text-lg text-on-dark-soft">{subheadline}</p>
            )}
        </AnimateInView>
    );
}
