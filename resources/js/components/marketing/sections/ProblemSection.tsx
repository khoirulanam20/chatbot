import { AnimatedSectionHeader } from '../aceternity/AnimatedSectionHeader';
import { SectionShell } from '../aceternity/SectionShell';
import { TextCompare } from '../aceternity/TextCompare';
import { problemCopy } from '@/content/marketing';

export function ProblemSection() {
    return (
        <SectionShell id="masalah" border className="bg-canvas">
            <AnimatedSectionHeader
                eyebrow={problemCopy.eyebrow}
                headline={problemCopy.headline}
                subheadline={problemCopy.subheadline}
            />
            <TextCompare problems={[...problemCopy.problems]} />
        </SectionShell>
    );
}
