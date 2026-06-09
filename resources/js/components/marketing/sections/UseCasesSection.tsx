import { Badge } from '@/components/ui/badge';
import { Card, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { motion } from 'framer-motion';
import { staggerContainer, springUp } from '../motion/variants';
import { SectionHeader } from '../SectionHeader';
import { useCasesCopy } from '@/content/marketing';

export function UseCasesSection() {
    return (
        <section id="use-case" className="py-20 sm:py-32">
            <div className="mx-auto max-w-6xl px-4 sm:px-6">
                <SectionHeader 
                    eyebrow={useCasesCopy.eyebrow}
                    headline={useCasesCopy.headline}
                    subheadline={useCasesCopy.subheadline}
                />
                
                <div className="mt-16 -mx-4 overflow-x-auto px-4 pb-8 sm:mx-0 sm:overflow-visible sm:px-0 sm:pb-0">
                    <motion.div 
                        className="flex w-max gap-6 sm:grid sm:w-auto sm:grid-cols-2 lg:grid-cols-4"
                        initial="hidden"
                        whileInView="visible"
                        viewport={{ once: true }}
                        variants={staggerContainer}
                    >
                        {useCasesCopy.cases.map((useCase) => (
                            <motion.div key={useCase.title} variants={springUp} className="w-72 sm:w-auto">
                                <Card className="h-full transition-shadow hover:shadow-md">
                                    <CardHeader>
                                        <div className="mb-4 flex items-center justify-between">
                                            <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-accent-muted text-primary">
                                                <useCase.icon className="h-6 w-6" />
                                            </div>
                                            <Badge variant="secondary" className="bg-canvas shadow-sm">{useCase.tag}</Badge>
                                        </div>
                                        <CardTitle className="text-lg">{useCase.title}</CardTitle>
                                        <CardDescription className="mt-2 text-sm leading-relaxed">{useCase.description}</CardDescription>
                                    </CardHeader>
                                </Card>
                            </motion.div>
                        ))}
                    </motion.div>
                </div>
            </div>
        </section>
    );
}
