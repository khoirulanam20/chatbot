import { HandoffIllustration } from '../illustrations/HandoffIllustration';
import { CheckCircle2 } from 'lucide-react';
import { Spotlight } from '@/components/ui/spotlight';
import { AnimatedSectionHeader } from '../aceternity/AnimatedSectionHeader';
import { SectionShell } from '../aceternity/SectionShell';
import { handoffCopy } from '@/content/marketing';

export function HandoffSection() {
    return (
        <SectionShell id="handoff" border className="bg-surface-soft">
            <div className="grid items-start gap-12 lg:grid-cols-2">
                <div>
                    <AnimatedSectionHeader
                        eyebrow={handoffCopy.eyebrow}
                        headline={handoffCopy.headline}
                        subheadline={handoffCopy.subheadline}
                        align="left"
                    />
                    <ul className="mt-8 space-y-4 text-sm text-on-dark-soft">
                        {handoffCopy.checklist.map((item, i) => (
                            <li key={i} className="flex items-start gap-3">
                                <CheckCircle2 className="h-5 w-5 shrink-0 text-success" />
                                <span>{item}</span>
                            </li>
                        ))}
                    </ul>
                </div>

                <div className="relative min-h-[360px] overflow-hidden rounded-2xl border border-white/[0.1] bg-zinc-900 shadow-lg">
                    <Spotlight className="-top-40 left-0 md:-top-20 md:left-60" fill="white" />
                    <HandoffIllustration />
                </div>
            </div>
        </SectionShell>
    );
}
