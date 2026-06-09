import { Bot, MessageSquare, Zap } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { HeroIllustration } from '../illustrations/HeroIllustration';
import { heroCopy } from '@/content/marketing';

export function HeroSection() {
    const scrollTo = (id: string) => {
        document.getElementById(id)?.scrollIntoView({ behavior: 'smooth' });
    };

    return (
        <section id="hero" className="relative overflow-hidden border-b border-hairline bg-canvas py-20 sm:py-32">
            <div className="absolute inset-0 bg-[linear-gradient(to_right,#80808012_1px,transparent_1px),linear-gradient(to_bottom,#80808012_1px,transparent_1px)] bg-[size:24px_24px]" />
            <div className="relative mx-auto grid max-w-6xl items-center gap-12 px-4 sm:px-6 lg:grid-cols-[1fr_1.2fr]">
                <div className="max-w-2xl">
                    <span className="mb-4 inline-block rounded-pill border border-hairline bg-surface-soft px-3 py-1 text-xs font-semibold tracking-widest text-muted uppercase shadow-sm">
                        {heroCopy.eyebrow}
                    </span>
                    <h1 className="font-display text-4xl font-bold tracking-tight text-ink sm:text-5xl lg:text-[3.5rem] lg:leading-[1.1]">
                        {heroCopy.headline}
                    </h1>
                    <p className="mt-6 text-lg text-body">
                        {heroCopy.subheadline}
                    </p>
                    <div className="mt-8 flex flex-wrap gap-3">
                        <Button size="lg" onClick={() => scrollTo('contact')}>
                            {heroCopy.ctaPrimary}
                        </Button>
                        <Button size="lg" variant="outline" onClick={() => scrollTo('cara-kerja')}>
                            {heroCopy.ctaSecondary}
                        </Button>
                    </div>
                </div>
                <HeroIllustration />
            </div>
        </section>
    );
}
