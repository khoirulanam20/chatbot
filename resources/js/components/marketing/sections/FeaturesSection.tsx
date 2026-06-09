import { BookOpen, Code, MessageCircle, Users, RotateCcw } from 'lucide-react';
import { useState } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { Card } from '@/components/ui/card';
import { AnimateInView } from '../motion/AnimateInView';
import { SectionHeader } from '../SectionHeader';
import { FeatureIllustration, FeatureType } from '../illustrations/FeatureIllustration';
import { featuresCopy } from '@/content/marketing';

const featureIcons: Record<FeatureType, typeof BookOpen> = {
    rag: BookOpen,
    widget: Code,
    whatsapp: MessageCircle,
    handoff: Users,
};

export function FeaturesSection() {
    const [activeIndex, setActiveIndex] = useState(0);
    const [replayKey, setReplayKey] = useState(0);

    const activeFeature = featuresCopy.features[activeIndex];
    const ActiveIcon = featureIcons[activeFeature.type];

    const selectFeature = (index: number) => {
        setActiveIndex(index);
        setReplayKey((k) => k + 1);
    };

    const handleReplay = () => setReplayKey((k) => k + 1);

    return (
        <section id="fitur" className="py-20 sm:py-32">
            <div className="mx-auto max-w-6xl px-4 sm:px-6">
                <SectionHeader
                    eyebrow={featuresCopy.eyebrow}
                    headline={featuresCopy.headline}
                    subheadline={featuresCopy.subheadline}
                />

                <AnimateInView className="mt-12">
                    <Card className="overflow-hidden border-hairline shadow-lg">
                        <div className="flex flex-col lg:flex-row">
                            {/* Feature selector */}
                            <div className="border-b border-hairline bg-surface-soft p-4 sm:p-6 lg:w-[340px] lg:border-b-0 lg:border-r">
                                <p className="mb-3 hidden text-xs font-medium text-muted lg:block">
                                    Klik fitur untuk melihat demo
                                </p>
                                <div className="flex gap-2 overflow-x-auto pb-1 lg:flex-col lg:overflow-visible lg:pb-0">
                                    {featuresCopy.features.map((feature, i) => {
                                        const Icon = featureIcons[feature.type];
                                        const isActive = activeIndex === i;

                                        return (
                                            <button
                                                key={feature.type}
                                                type="button"
                                                onClick={() => selectFeature(i)}
                                                className={`flex min-w-[200px] shrink-0 items-start gap-3 rounded-xl px-4 py-3 text-left transition-all duration-200 lg:min-w-0 lg:w-full ${
                                                    isActive
                                                        ? 'bg-canvas shadow-sm ring-2 ring-primary'
                                                        : 'text-muted hover:bg-canvas/60 hover:text-ink'
                                                }`}
                                            >
                                                <div
                                                    className={`mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg transition-colors ${
                                                        isActive
                                                            ? 'bg-accent-muted text-primary'
                                                            : 'bg-canvas text-muted'
                                                    }`}
                                                >
                                                    <Icon className="h-4 w-4" />
                                                </div>
                                                <div>
                                                    <div className="flex items-center gap-2">
                                                        <span className="font-display text-xs font-bold text-muted">
                                                            0{i + 1}
                                                        </span>
                                                        <span
                                                            className={`text-sm font-semibold ${
                                                                isActive ? 'text-ink' : ''
                                                            }`}
                                                        >
                                                            {feature.title}
                                                        </span>
                                                    </div>
                                                    <p className="mt-1 hidden text-xs leading-relaxed text-muted lg:block">
                                                        {feature.description}
                                                    </p>
                                                </div>
                                            </button>
                                        );
                                    })}
                                </div>
                            </div>

                            {/* Showcase panel */}
                            <div className="relative flex-1 bg-surface-card p-6 sm:p-8">
                                <AnimatePresence mode="wait">
                                    <motion.div
                                        key={`${activeFeature.type}-${replayKey}`}
                                        initial={{ opacity: 0, y: 12 }}
                                        animate={{ opacity: 1, y: 0 }}
                                        exit={{ opacity: 0, y: -12 }}
                                        transition={{ duration: 0.25 }}
                                        className="flex h-full flex-col"
                                    >
                                        <div className="mb-4 flex items-start justify-between gap-4 lg:hidden">
                                            <div>
                                                <div className="flex items-center gap-2">
                                                    <ActiveIcon className="h-4 w-4 text-primary" />
                                                    <h3 className="font-display font-semibold text-ink">
                                                        {activeFeature.title}
                                                    </h3>
                                                </div>
                                                <p className="mt-1 text-sm text-muted">
                                                    {activeFeature.description}
                                                </p>
                                            </div>
                                        </div>

                                        <FeatureIllustration
                                            type={activeFeature.type}
                                            variant="showcase"
                                            forceActive
                                        />

                                        <div className="mt-4 hidden lg:block">
                                            <h3 className="font-display text-lg font-semibold text-ink">
                                                {activeFeature.title}
                                            </h3>
                                            <p className="mt-1 text-sm text-muted">
                                                {activeFeature.description}
                                            </p>
                                        </div>

                                        <div className="mt-4 flex items-center justify-between border-t border-hairline pt-4">
                                            <p className="text-xs text-muted">
                                                Klik elemen di ilustrasi untuk interaksi
                                            </p>
                                            <motion.button
                                                type="button"
                                                whileTap={{ scale: 0.95 }}
                                                onClick={handleReplay}
                                                className="flex items-center gap-1.5 rounded-lg border border-hairline bg-canvas px-3 py-1.5 text-xs font-medium text-muted transition-colors hover:border-primary/30 hover:text-primary"
                                            >
                                                <RotateCcw className="h-3 w-3" />
                                                Putar Ulang
                                            </motion.button>
                                        </div>
                                    </motion.div>
                                </AnimatePresence>
                            </div>
                        </div>
                    </Card>
                </AnimateInView>
            </div>
        </section>
    );
}
