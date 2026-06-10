import {
    Accordion,
    AccordionContent,
    AccordionItem,
    AccordionTrigger,
} from '@/components/ui/accordion';
import { SparklesCore } from '@/components/ui/sparkles';
import { AnimatedSectionHeader } from '../aceternity/AnimatedSectionHeader';
import { faqCopy } from '@/content/marketing';
import { useReducedMotion } from '../motion/useReducedMotion';

export function FaqSection() {
    const reducedMotion = useReducedMotion();

    const faqJsonLd = {
        '@context': 'https://schema.org',
        '@type': 'FAQPage',
        mainEntity: faqCopy.faqs.map((faq) => ({
            '@type': 'Question',
            name: faq.question,
            acceptedAnswer: {
                '@type': 'Answer',
                text: faq.answer,
            },
        })),
    };

    return (
        <section id="faq" className="relative overflow-hidden bg-surface-dark py-20 sm:py-32">
            <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(faqJsonLd) }} />

            {!reducedMotion && (
                <div className="pointer-events-none absolute inset-0 h-full w-full">
                    <SparklesCore
                        background="transparent"
                        minSize={0.2}
                        maxSize={0.8}
                        particleDensity={40}
                        className="h-full w-full"
                        particleColor="#FFFFFF"
                    />
                </div>
            )}

            <div className="relative mx-auto max-w-3xl px-4 sm:px-6">
                <AnimatedSectionHeader
                    eyebrow={faqCopy.eyebrow}
                    headline={faqCopy.headline}
                />
                <div className="mt-12">
                    <Accordion type="single" collapsible className="rounded-xl border border-white/10 bg-surface-dark-elevated px-6 text-on-dark">
                        {faqCopy.faqs.map((faq, i) => (
                            <AccordionItem key={i} value={`item-${i}`} className="border-white/10">
                                <AccordionTrigger className="text-left text-on-dark hover:text-on-dark/80">
                                    {faq.question}
                                </AccordionTrigger>
                                <AccordionContent className="text-on-dark-soft">{faq.answer}</AccordionContent>
                            </AccordionItem>
                        ))}
                    </Accordion>
                </div>
            </div>
        </section>
    );
}
