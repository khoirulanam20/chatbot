import { XCircle, CheckCircle2, ArrowRight } from 'lucide-react';
import { Card, CardContent } from '@/components/ui/card';
import { AnimateInView } from '../motion/AnimateInView';
import { motion } from 'framer-motion';
import { staggerContainer, springUp } from '../motion/variants';
import { SectionHeader } from '../SectionHeader';
import { problemCopy } from '@/content/marketing';

export function ProblemSection() {
    return (
        <section id="masalah" className="border-y border-hairline bg-surface-soft py-20 sm:py-32">
            <div className="mx-auto max-w-6xl px-4 sm:px-6">
                <SectionHeader 
                    eyebrow={problemCopy.eyebrow}
                    headline={problemCopy.headline}
                    subheadline={problemCopy.subheadline}
                />
                
                <motion.div 
                    className="mt-16 grid gap-6"
                    initial="hidden"
                    whileInView="visible"
                    viewport={{ once: true }}
                    variants={staggerContainer}
                >
                    {problemCopy.problems.map((problem, i) => (
                        <motion.div key={i} variants={springUp}>
                            <Card className="overflow-hidden border-none bg-canvas shadow-sm">
                                <CardContent className="p-0">
                                    <div className="grid sm:grid-cols-[1fr_auto_1fr] items-center">
                                        <div className="flex items-start gap-4 border-l-4 border-error p-6 sm:p-8">
                                            <XCircle className="h-6 w-6 shrink-0 text-error mt-0.5" />
                                            <p className="text-ink font-medium">{problem.before}</p>
                                        </div>
                                        
                                        <div className="flex justify-center -my-3 sm:my-0 sm:-mx-3 relative z-10">
                                            <div className="bg-canvas rounded-full p-2 border border-hairline shadow-sm rotate-90 sm:rotate-0">
                                                <ArrowRight className="h-5 w-5 text-muted" />
                                            </div>
                                        </div>
                                        
                                        <div className="flex items-start gap-4 border-l-4 border-primary p-6 sm:p-8 bg-accent-muted/30">
                                            <CheckCircle2 className="h-6 w-6 shrink-0 text-primary mt-0.5" />
                                            <p className="text-ink font-medium">{problem.after}</p>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </motion.div>
                    ))}
                </motion.div>
            </div>
        </section>
    );
}
