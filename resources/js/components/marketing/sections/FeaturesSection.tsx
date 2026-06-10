import { BookOpen, Code, MessageCircle, Users, RotateCcw } from 'lucide-react';
import { useState } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { ContainerScroll } from '@/components/ui/container-scroll-animation';
import { BentoGrid, BentoGridItem } from '@/components/ui/bento-grid';
import { AnimatedSectionHeader } from '../aceternity/AnimatedSectionHeader';
import { SectionShell } from '../aceternity/SectionShell';
import { FeatureIllustration, FeatureType } from '../illustrations/FeatureIllustration';
import { featuresCopy } from '@/content/marketing';
import { useMediaQuery } from '@/lib/useMediaQuery';
import { useReducedMotion } from '../motion/useReducedMotion';

const featureIcons: Record<FeatureType, typeof BookOpen> = {
    rag: BookOpen,
    widget: Code,
    whatsapp: MessageCircle,
    handoff: Users,
};

export function FeaturesSection() {
    const [activeIndex, setActiveIndex] = useState(0);
    const [replayKey, setReplayKey] = useState(0);
    const isDesktop = useMediaQuery('(min-width: 1024px)');
    const reducedMotion = useReducedMotion();

    const activeFeature = featuresCopy.features[activeIndex];
    const ActiveIcon = featureIcons[activeFeature.type];

    const selectFeature = (index: number) => {
        setActiveIndex(index);
        setReplayKey((k) => k + 1);
    };

    const handleReplay = () => setReplayKey((k) => k + 1);

    const demoPanel = (
        <AnimatePresence mode="wait">
            <motion.div
                key={`${activeFeature.type}-${replayKey}`}
                initial={{ opacity: 0, y: 12 }}
                animate={{ opacity: 1, y: 0 }}
                exit={{ opacity: 0, y: -12 }}
                transition={{ duration: 0.25 }}
                className="flex h-full flex-col"
            >
                <FeatureIllustration
                    type={activeFeature.type}
                    variant="showcase"
                    forceActive
                />
                <div className="mt-4 flex items-center justify-between border-t border-hairline pt-4">
                    <p className="text-xs text-muted">Klik elemen di ilustrasi untuk interaksi</p>
                    <motion.button
                        type="button"
                        whileTap={{ scale: 0.95 }}
                        onClick={handleReplay}
                        className="flex items-center gap-1.5 rounded-lg border border-white/[0.1] bg-zinc-800 px-3 py-1.5 text-xs font-medium text-muted transition-colors hover:border-primary/30 hover:text-primary"
                    >
                        <RotateCcw className="h-3 w-3" />
                        Putar Ulang
                    </motion.button>
                </div>
            </motion.div>
        </AnimatePresence>
    );

    return (
        <SectionShell id="fitur" withSpotlight className="bg-surface-soft">
            <AnimatedSectionHeader
                eyebrow={featuresCopy.eyebrow}
                headline={featuresCopy.headline}
                subheadline={featuresCopy.subheadline}
            />

            <div className="mt-8 flex gap-2 overflow-x-auto pb-2 lg:hidden">
                {featuresCopy.features.map((feature, i) => {
                    const Icon = featureIcons[feature.type];
                    const isActive = activeIndex === i;
                    return (
                        <button
                            key={feature.type}
                            type="button"
                            onClick={() => selectFeature(i)}
                            className={`flex shrink-0 items-center gap-2 rounded-pill px-4 py-2 text-sm ${
                                isActive ? 'bg-primary text-on-primary' : 'border border-white/[0.1] bg-zinc-900 text-muted'
                            }`}
                        >
                            <Icon className="h-4 w-4" />
                            {feature.title}
                        </button>
                    );
                })}
            </div>

            {isDesktop && !reducedMotion ? (
                <ContainerScroll
                    titleComponent={
                        <div className="flex flex-wrap justify-center gap-2">
                            {featuresCopy.features.map((feature, i) => {
                                const Icon = featureIcons[feature.type];
                                const isActive = activeIndex === i;
                                return (
                                    <button
                                        key={feature.type}
                                        type="button"
                                        onClick={() => selectFeature(i)}
                                        className={`flex items-center gap-2 rounded-pill px-4 py-2 text-sm transition-all ${
                                            isActive
                                                ? 'bg-primary text-on-primary'
                                                : 'border border-white/[0.1] text-muted hover:text-on-dark'
                                        }`}
                                    >
                                        <Icon className="h-4 w-4" />
                                        {feature.title}
                                    </button>
                                );
                            })}
                        </div>
                    }
                >
                    {demoPanel}
                </ContainerScroll>
            ) : (
                <div className="mt-12">
                    <BentoGrid className="max-w-6xl md:auto-rows-[auto] md:grid-cols-[1fr_2fr]">
                        <div className="hidden flex-col gap-2 lg:flex">
                            {featuresCopy.features.map((feature, i) => {
                                const Icon = featureIcons[feature.type];
                                const isActive = activeIndex === i;
                                return (
                                    <button
                                        key={feature.type}
                                        type="button"
                                        onClick={() => selectFeature(i)}
                                        className={`flex items-start gap-3 rounded-xl px-4 py-3 text-left transition-all ${
                                            isActive
                                                ? 'bg-zinc-800 ring-2 ring-primary'
                                                : 'text-muted hover:bg-zinc-900 hover:text-on-dark'
                                        }`}
                                    >
                                        <div className={`flex h-9 w-9 shrink-0 items-center justify-center rounded-lg ${
                                            isActive ? 'bg-primary/20 text-primary' : 'bg-zinc-800 text-muted'
                                        }`}>
                                            <Icon className="h-4 w-4" />
                                        </div>
                                        <div>
                                            <span className="text-sm font-semibold text-on-dark">{feature.title}</span>
                                            <p className="mt-1 text-xs text-muted">{feature.description}</p>
                                        </div>
                                    </button>
                                );
                            })}
                        </div>
                        <BentoGridItem
                            className="md:col-span-1 min-h-[20rem]"
                            title={
                                <div className="flex items-center gap-2">
                                    <ActiveIcon className="h-4 w-4 text-primary" />
                                    <span>{activeFeature.title}</span>
                                </div>
                            }
                            description={activeFeature.description}
                            header={<div className="p-2">{demoPanel}</div>}
                        />
                    </BentoGrid>
                </div>
            )}
        </SectionShell>
    );
}
