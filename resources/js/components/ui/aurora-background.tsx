import { cn } from '@/lib/utils';
import React, { ReactNode } from 'react';

interface AuroraBackgroundProps extends React.HTMLProps<HTMLDivElement> {
    children: ReactNode;
    showRadialGradient?: boolean;
}

export const AuroraBackground = ({
    className,
    children,
    showRadialGradient = true,
    ...props
}: AuroraBackgroundProps) => {
    return (
        <main>
            <div
                className={cn(
                    'relative flex flex-col bg-canvas text-ink transition-bg',
                    className,
                )}
                {...props}
            >
                <div className="absolute inset-0 overflow-hidden">
                    <div
                        className={cn(
                            `
                            pointer-events-none absolute -inset-[10px] opacity-40
                            [--aurora:repeating-linear-gradient(100deg,var(--color-primary)_10%,var(--color-brand-accent)_15%,#6366f1_20%,#8b5cf6_25%,var(--color-primary)_30%)]
                            [--white-gradient:repeating-linear-gradient(100deg,#fff_0%,#fff_7%,transparent_10%,transparent_12%,#fff_16%)]
                            [background-image:var(--white-gradient),var(--aurora)]
                            [background-size:300%,_200%]
                            [background-position:50%_50%,50%_50%]
                            filter blur-[10px]
                            after:absolute after:inset-0 after:animate-aurora after:opacity-70 after:[background-image:var(--white-gradient),var(--aurora)] after:[background-size:200%,_100%] after:[background-attachment:fixed] after:content-['']
                            `,
                            showRadialGradient &&
                                '[mask-image:radial-gradient(ellipse_at_100%_0%,black_10%,transparent_70%)]',
                        )}
                    />
                </div>
                {children}
            </div>
        </main>
    );
};
