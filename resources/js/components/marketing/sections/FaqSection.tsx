import {
    Accordion,
    AccordionContent,
    AccordionItem,
    AccordionTrigger,
} from '@/components/ui/accordion';
import { AnimateInView } from '../motion/AnimateInView';
import { faqCopy } from '@/content/marketing';

export function FaqSection() {
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
        <section id="faq" className="bg-surface-dark py-20 sm:py-32">
            <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(faqJsonLd) }} />
            <div className="mx-auto max-w-3xl px-4 sm:px-6">
                <AnimateInView className="text-center">
                    <span className="mb-3 inline-block rounded-pill border border-white/10 bg-white/5 px-3 py-1 text-xs font-semibold tracking-widest text-on-dark-soft uppercase shadow-sm">
                        {faqCopy.eyebrow}
                    </span>
                    <h2 className="font-display text-3xl font-bold text-on-dark sm:text-4xl">
                        {faqCopy.headline}
                    </h2>
                </AnimateInView>
                <AnimateInView className="mt-12">
                    <Accordion type="single" collapsible className="rounded-xl border border-white/10 bg-surface-dark-elevated px-6 text-on-dark">
                        {faqCopy.faqs.map((faq, i) => (
                            <AccordionItem key={i} value={`item-${i}`} className="border-white/10">
                                <AccordionTrigger className="text-left text-on-dark hover:text-on-dark/80">{faq.question}</AccordionTrigger>
                                <AccordionContent className="text-on-dark-soft">{faq.answer}</AccordionContent>
                            </AccordionItem>
                        ))}
                    </Accordion>
                </AnimateInView>
            </div>
        </section>
    );
}
