import { Head } from '@inertiajs/react';

interface SeoHeadProps {
    title: string;
    description: string;
    url?: string;
    jsonLd?: Record<string, unknown>;
}

export function SeoHead({ title, description, url, jsonLd }: SeoHeadProps) {
    const canonical = url ?? (typeof window !== 'undefined' ? window.location.origin : '');
    const ogImage = canonical ? `${canonical.replace(/\/$/, '')}/og-image.svg` : '/og-image.svg';

    return (
        <Head title={title}>
            <meta head-key="description" name="description" content={description} />
            <link head-key="canonical" rel="canonical" href={canonical} />
            <meta head-key="og:title" property="og:title" content={title} />
            <meta head-key="og:description" property="og:description" content={description} />
            <meta head-key="og:type" property="og:type" content="website" />
            <meta head-key="og:url" property="og:url" content={canonical} />
            <meta head-key="og:image" property="og:image" content={ogImage} />
            <meta head-key="twitter:card" name="twitter:card" content="summary_large_image" />
            <meta head-key="twitter:title" name="twitter:title" content={title} />
            <meta head-key="twitter:description" name="twitter:description" content={description} />
            <meta head-key="twitter:image" name="twitter:image" content={ogImage} />
            {jsonLd && (
                <script
                    head-key="json-ld"
                    type="application/ld+json"
                    dangerouslySetInnerHTML={{ __html: JSON.stringify(jsonLd) }}
                />
            )}
        </Head>
    );
}
