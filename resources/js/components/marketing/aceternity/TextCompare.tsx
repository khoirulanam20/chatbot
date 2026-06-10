import { useState } from 'react';
import { Compare } from '@/components/ui/compare';
import { cn } from '@/lib/utils';
import { XCircle, CheckCircle2 } from 'lucide-react';

interface ProblemItem {
    before: string;
    after: string;
}

interface TextCompareProps {
    problems: ProblemItem[];
}

export function TextCompare({ problems }: TextCompareProps) {
    const [activeIndex, setActiveIndex] = useState(0);
    const active = problems[activeIndex];

    return (
        <div className="mt-12 space-y-6">
            <div className="flex flex-wrap justify-center gap-2">
                {problems.map((_, i) => (
                    <button
                        key={i}
                        type="button"
                        onClick={() => setActiveIndex(i)}
                        className={cn(
                            'rounded-pill px-4 py-2 text-sm font-medium transition-all',
                            activeIndex === i
                                ? 'bg-primary text-on-primary'
                                : 'border border-white/[0.1] bg-surface-dark-elevated text-on-dark-soft hover:text-on-dark',
                        )}
                    >
                        Masalah {i + 1}
                    </button>
                ))}
            </div>

            <Compare
                slideMode="drag"
                className="h-[280px] rounded-2xl border border-white/[0.1] md:h-[220px]"
                firstContent={
                    <div className="flex h-full items-center bg-red-950/40 p-6 md:p-8">
                        <div className="flex gap-3">
                            <XCircle className="mt-1 h-5 w-5 shrink-0 text-red-400" />
                            <p className="text-sm leading-relaxed text-on-dark md:text-base">{active.before}</p>
                        </div>
                    </div>
                }
                secondContent={
                    <div className="flex h-full items-center bg-emerald-950/30 p-6 md:p-8">
                        <div className="flex gap-3">
                            <CheckCircle2 className="mt-1 h-5 w-5 shrink-0 text-emerald-400" />
                            <p className="text-sm leading-relaxed text-on-dark md:text-base">{active.after}</p>
                        </div>
                    </div>
                }
            />
            <p className="text-center text-xs text-muted">Geser slider untuk melihat sebelum & sesudah</p>
        </div>
    );
}
