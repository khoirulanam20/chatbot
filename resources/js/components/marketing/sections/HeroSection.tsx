import { Bot, MessageSquare, Zap } from 'lucide-react';
import { AuroraBackground } from '@/components/ui/aurora-background';
import { Spotlight } from '@/components/ui/spotlight';
import { SparklesCore } from '@/components/ui/sparkles';
import { MovingBorderButton } from '@/components/ui/moving-border';
import { InfiniteMovingCards } from '@/components/ui/infinite-moving-cards';
import { AnimatedSectionHeader } from '../aceternity/AnimatedSectionHeader';
import { InteractiveChatPreview } from '../aceternity/InteractiveChatPreview';
import { useScrollTo } from '../aceternity/useScrollTo';
import { heroCopy } from '@/content/marketing';
import { useReducedMotion } from '../motion/useReducedMotion';

const trustIcons: Record<string, typeof Zap> = {
    Zap,
    MessageSquare,
    Bot,
};

export function HeroSection() {
    const scrollTo = useScrollTo();
    const reducedMotion = useReducedMotion();

    const trustItems = heroCopy.trustRow.map((item) => ({
        quote: item.text,
        name: item.text,
        title: '',
    }));

    return (
        <AuroraBackground className="min-h-[90vh] border-b border-hairline">
            <div className="relative flex min-h-[90vh] flex-col items-center justify-center px-4 py-20 sm:px-6">
                {!reducedMotion && (
                    <>
                        <Spotlight className="-top-40 left-0 md:-top-20 md:left-60" fill="white" />
                        <div className="pointer-events-none absolute inset-0 h-full w-full">
                            <SparklesCore
                                background="transparent"
                                minSize={0.4}
                                maxSize={1}
                                particleDensity={80}
                                className="h-full w-full"
                                particleColor="#FFFFFF"
                            />
                        </div>
                    </>
                )}

                <div className="relative z-10 mx-auto grid w-full max-w-6xl items-center gap-12 lg:grid-cols-[1fr_1.2fr]">
                    <div className="max-w-2xl">
                        <AnimatedSectionHeader
                            eyebrow={heroCopy.eyebrow}
                            headline={heroCopy.headline}
                            subheadline={heroCopy.subheadline}
                            align="left"
                            typewriter={!reducedMotion}
                        />
                        <div className="mt-8 flex flex-wrap gap-4">
                            <MovingBorderButton
                                borderRadius="0.75rem"
                                containerClassName="h-12 w-auto"
                                className="border-slate-800 bg-slate-900/90 px-6 text-sm font-semibold text-on-dark"
                                onClick={() => scrollTo('contact')}
                            >
                                {heroCopy.ctaPrimary}
                            </MovingBorderButton>
                            <MovingBorderButton
                                borderRadius="0.75rem"
                                containerClassName="h-12 w-auto"
                                className="border-slate-800 bg-slate-900/90 px-6 text-sm font-semibold text-on-dark"
                                onClick={() => scrollTo('cara-kerja')}
                            >
                                {heroCopy.ctaSecondary}
                            </MovingBorderButton>
                        </div>

                        <div className="mt-8 flex flex-wrap gap-4">
                            {heroCopy.trustRow.map((item) => {
                                const Icon = trustIcons[item.icon] ?? Bot;
                                return (
                                    <div
                                        key={item.text}
                                        className="flex items-center gap-2 rounded-pill border border-white/[0.1] bg-black/40 px-3 py-1.5 text-xs text-on-dark-soft backdrop-blur-sm"
                                    >
                                        <Icon className="h-3.5 w-3.5 text-primary" />
                                        {item.text}
                                    </div>
                                );
                            })}
                        </div>
                    </div>

                    <InteractiveChatPreview />
                </div>

                {!reducedMotion && (
                    <div className="relative z-10 mt-16 w-full max-w-6xl">
                        <InfiniteMovingCards
                            items={trustItems}
                            direction="right"
                            speed="slow"
                        />
                    </div>
                )}
            </div>
        </AuroraBackground>
    );
}
