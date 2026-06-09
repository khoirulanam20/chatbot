import { Card } from '@/components/ui/card';
import { HandoffIllustration } from '../illustrations/HandoffIllustration';
import { AnimateInView } from '../motion/AnimateInView';
import { CheckCircle2 } from 'lucide-react';
import { SectionHeader } from '../SectionHeader';
import { handoffCopy } from '@/content/marketing';

export function HandoffSection() {
    return (
        <section id="handoff" className="border-y border-hairline bg-surface-soft py-20 sm:py-32">
            <div className="mx-auto max-w-6xl px-4 sm:px-6">
                <div className="grid items-start gap-12 lg:grid-cols-2">
                    <AnimateInView>
                        <SectionHeader 
                            eyebrow={handoffCopy.eyebrow}
                            headline={handoffCopy.headline}
                            subheadline={handoffCopy.subheadline}
                            align="left"
                        />
                        <ul className="mt-8 space-y-4 text-sm text-body">
                            {handoffCopy.checklist.map((item, i) => (
                                <li key={i} className="flex items-start gap-3">
                                    <CheckCircle2 className="h-5 w-5 shrink-0 text-success" />
                                    <span>{item}</span>
                                </li>
                            ))}
                        </ul>
                    </AnimateInView>
                    <AnimateInView>
                        <Card className="min-h-[360px] overflow-hidden shadow-lg">
                            <HandoffIllustration />
                        </Card>
                    </AnimateInView>
                </div>
            </div>
        </section>
    );
}
