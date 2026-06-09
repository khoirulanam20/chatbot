import { useState } from 'react';
import { Card, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { HowItWorksIllustration } from '../illustrations/HowItWorksIllustration';
import { AnimateInView } from '../motion/AnimateInView';
import { staggerContainer, springUp } from '../motion/variants';
import { motion } from 'framer-motion';
import { SectionHeader } from '../SectionHeader';
import { howItWorksCopy } from '@/content/marketing';

export function HowItWorksSection() {
    const [activeStep, setActiveStep] = useState(0);

    return (
        <section id="cara-kerja" className="border-y border-hairline bg-surface-soft py-20 sm:py-32">
            <div className="mx-auto max-w-6xl px-4 sm:px-6">
                <SectionHeader 
                    eyebrow={howItWorksCopy.eyebrow}
                    headline={howItWorksCopy.headline}
                    subheadline={howItWorksCopy.subheadline}
                />
                
                <AnimateInView className="mt-16">
                    <HowItWorksIllustration onStepChange={setActiveStep} />
                </AnimateInView>
                
                <motion.div 
                    className="grid gap-6 md:grid-cols-3"
                    initial="hidden"
                    whileInView="visible"
                    viewport={{ once: true }}
                    variants={staggerContainer}
                >
                    {howItWorksCopy.steps.map((step, i) => (
                        <motion.div key={step.title} variants={springUp}>
                            <Card
                                onClick={() => setActiveStep(i)}
                                className={`h-full cursor-pointer transition-all duration-300 ${
                                    activeStep === i 
                                        ? 'ring-2 ring-primary shadow-md -translate-y-1' 
                                        : 'hover:border-primary/50 hover:shadow-sm'
                                }`}
                            >
                                <CardHeader>
                                    <div className="mb-4 font-display text-4xl font-bold text-surface-strong">
                                        0{i + 1}
                                    </div>
                                    <CardTitle className="text-xl">{step.title}</CardTitle>
                                    <CardDescription className="text-base">{step.description}</CardDescription>
                                </CardHeader>
                            </Card>
                        </motion.div>
                    ))}
                </motion.div>
            </div>
        </section>
    );
}
