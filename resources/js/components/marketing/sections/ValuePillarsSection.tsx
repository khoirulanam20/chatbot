import { WobbleCard } from '@/components/ui/wobble-card';
import { AnimatedSectionHeader } from '../aceternity/AnimatedSectionHeader';
import { SectionShell } from '../aceternity/SectionShell';
import { valuePillarsCopy } from '@/content/marketing';

export function ValuePillarsSection() {
    return (
        <SectionShell id="kenapa-kami" className="bg-surface-soft">
            <AnimatedSectionHeader
                eyebrow={valuePillarsCopy.eyebrow}
                headline={valuePillarsCopy.headline}
            />

            <div className="mt-16 grid gap-4 sm:grid-cols-2">
                {valuePillarsCopy.pillars.map((pillar, i) => (
                    <WobbleCard
                        key={pillar.title}
                        containerClassName={i < 2 ? 'sm:col-span-1' : ''}
                        className="h-full p-6"
                    >
                        <div className="flex h-full min-h-[12rem] flex-col justify-between">
                            <div className="mb-4 h-24 rounded-xl bg-gradient-to-br from-primary/20 to-brand-accent/10" />
                            <div>
                                <div className="mb-3 flex items-center gap-3">
                                    <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/20 text-primary">
                                        <pillar.icon className="h-5 w-5" />
                                    </div>
                                    <span className="font-display text-sm font-bold text-muted">0{i + 1}</span>
                                </div>
                                <h3 className="font-display font-bold text-on-dark">{pillar.title}</h3>
                                <p className="mt-2 text-sm text-on-dark-soft">{pillar.description}</p>
                            </div>
                        </div>
                    </WobbleCard>
                ))}
            </div>
        </SectionShell>
    );
}
