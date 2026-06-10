import { useState } from 'react';
import { Timeline } from '@/components/ui/timeline';
import { TracingBeam } from '@/components/ui/tracing-beam';
import { HowItWorksIllustration } from '../illustrations/HowItWorksIllustration';
import { AnimatedSectionHeader } from '../aceternity/AnimatedSectionHeader';
import { SectionShell } from '../aceternity/SectionShell';
import { howItWorksCopy } from '@/content/marketing';

export function HowItWorksSection() {
    const [activeStep, setActiveStep] = useState(0);

    const timelineData = howItWorksCopy.steps.map((step, i) => ({
        title: step.title,
        content: (
            <div>
                <p className="mb-8 text-xs font-normal text-on-dark-soft md:text-sm">{step.description}</p>
                {i === 0 && (
                    <div className="mb-8">
                        <HowItWorksIllustration onStepChange={setActiveStep} />
                    </div>
                )}
                <button
                    type="button"
                    onClick={() => setActiveStep(i)}
                    className={`rounded-lg border px-4 py-2 text-sm transition-all ${
                        activeStep === i
                            ? 'border-primary bg-primary/10 text-primary'
                            : 'border-white/[0.1] text-muted hover:text-on-dark'
                    }`}
                >
                    Lihat langkah {i + 1}
                </button>
            </div>
        ),
    }));

    return (
        <SectionShell id="cara-kerja" border className="bg-canvas">
            <AnimatedSectionHeader
                eyebrow={howItWorksCopy.eyebrow}
                headline={howItWorksCopy.headline}
                subheadline={howItWorksCopy.subheadline}
            />

            <TracingBeam className="mt-16 max-w-6xl px-6">
                <Timeline data={timelineData} />
            </TracingBeam>
        </SectionShell>
    );
}
