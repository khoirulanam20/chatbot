import { MarketingLayout } from '@/components/marketing/MarketingLayout';
import { SeoHead } from '@/components/marketing/SeoHead';
import { BrandTheme } from '@/components/marketing/BrandTheme';
import { ChannelsSection } from '@/components/marketing/sections/ChannelsSection';
import { ContactSection } from '@/components/marketing/sections/ContactSection';
import { FaqSection } from '@/components/marketing/sections/FaqSection';
import { FeaturesSection } from '@/components/marketing/sections/FeaturesSection';
import { HandoffSection } from '@/components/marketing/sections/HandoffSection';
import { HeroSection } from '@/components/marketing/sections/HeroSection';
import { HowItWorksSection } from '@/components/marketing/sections/HowItWorksSection';
import { ProblemSection } from '@/components/marketing/sections/ProblemSection';
import { UseCasesSection } from '@/components/marketing/sections/UseCasesSection';
import { ValuePillarsSection } from '@/components/marketing/sections/ValuePillarsSection';
import { seoCopy } from '@/content/marketing';

interface BrandColors {
    primary: string;
    primary_active: string;
    brand_accent: string;
    accent_muted: string;
    ink: string;
}

interface LandingProps {
    contactWhatsApp: string;
    appUrl: string;
    brand: BrandColors;
}

export default function Landing({ contactWhatsApp, appUrl, brand }: LandingProps) {
    const jsonLd = {
        '@context': 'https://schema.org',
        '@type': 'SoftwareApplication',
        name: 'AI CS Chatbot',
        applicationCategory: 'BusinessApplication',
        operatingSystem: 'Web',
        description: seoCopy.description,
        url: appUrl,
        offers: {
            '@type': 'Offer',
            price: '0',
            priceCurrency: 'IDR',
            description: 'Hubungi kami untuk demo dan penawaran',
        },
    };

    return (
        <MarketingLayout>
            <BrandTheme brand={brand} />
            <SeoHead title={seoCopy.title} description={seoCopy.description} url={appUrl} jsonLd={jsonLd} />
            <HeroSection />
            <ValuePillarsSection />
            <ProblemSection />
            <FeaturesSection />
            <HowItWorksSection />
            <ChannelsSection />
            <HandoffSection />
            <UseCasesSection />
            <FaqSection />
            <ContactSection contactWhatsApp={contactWhatsApp} />
        </MarketingLayout>
    );
}
