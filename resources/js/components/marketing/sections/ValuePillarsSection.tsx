import { AnimateInView } from '../motion/AnimateInView';
import { motion } from 'framer-motion';
import { staggerContainer, springUp } from '../motion/variants';
import { SectionHeader } from '../SectionHeader';
import { valuePillarsCopy } from '@/content/marketing';

export function ValuePillarsSection() {
    return (
        <section id="kenapa-kami" className="bg-surface-soft py-20 sm:py-32">
            <div className="mx-auto max-w-6xl px-4 sm:px-6">
                <SectionHeader 
                    eyebrow={valuePillarsCopy.eyebrow}
                    headline={valuePillarsCopy.headline}
                />
                
                <motion.div 
                    className="mt-16 grid gap-8 sm:grid-cols-2 lg:grid-cols-4"
                    initial="hidden"
                    whileInView="visible"
                    viewport={{ once: true }}
                    variants={staggerContainer}
                >
                    {valuePillarsCopy.pillars.map((pillar, i) => (
                        <motion.div key={pillar.title} variants={springUp} className="flex flex-col gap-4">
                            <div className="flex items-start gap-4 lg:flex-col">
                                <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-accent-muted text-primary">
                                    <pillar.icon className="h-6 w-6" />
                                </div>
                                <div>
                                    <div className="mb-1 font-display text-sm font-bold text-muted">0{i + 1}</div>
                                    <h3 className="font-semibold text-ink">{pillar.title}</h3>
                                </div>
                            </div>
                            <p className="text-sm text-muted">{pillar.description}</p>
                        </motion.div>
                    ))}
                </motion.div>
            </div>
        </section>
    );
}
