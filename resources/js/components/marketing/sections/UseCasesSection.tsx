import { FocusCards } from '@/components/ui/focus-cards';
import { AnimatedSectionHeader } from '../aceternity/AnimatedSectionHeader';
import { SectionShell } from '../aceternity/SectionShell';
import { useCasesCopy } from '@/content/marketing';

export function UseCasesSection() {
    const cards = useCasesCopy.cases.map((useCase) => ({
        title: `${useCase.tag} — ${useCase.title}`,
        src: '',
    }));

    return (
        <SectionShell id="use-case" className="bg-canvas">
            <AnimatedSectionHeader
                eyebrow={useCasesCopy.eyebrow}
                headline={useCasesCopy.headline}
                subheadline={useCasesCopy.subheadline}
            />

            <div className="mt-16">
                <FocusCards cards={cards} />
            </div>

            <div className="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                {useCasesCopy.cases.map((useCase) => (
                    <div
                        key={useCase.title}
                        className="rounded-xl border border-white/[0.1] bg-surface-dark-elevated p-4"
                    >
                        <div className="mb-3 flex h-10 w-10 items-center justify-center rounded-lg bg-primary/20 text-primary">
                            <useCase.icon className="h-5 w-5" />
                        </div>
                        <p className="text-sm leading-relaxed text-on-dark-soft">{useCase.description}</p>
                    </div>
                ))}
            </div>
        </SectionShell>
    );
}
