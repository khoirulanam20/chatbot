import { useEffect } from 'react';

interface BrandColors {
    primary: string;
    primary_active: string;
    brand_accent: string;
    accent_muted: string;
    ink: string;
}

interface BrandThemeProps {
    brand: BrandColors;
}

export function BrandTheme({ brand }: BrandThemeProps) {
    useEffect(() => {
        const root = document.documentElement;
        
        // Helper to convert hex to HSL for shadcn compatibility
        const hexToHSL = (hex: string) => {
            const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
            if (!result) return null;
            
            let r = parseInt(result[1], 16) / 255;
            let g = parseInt(result[2], 16) / 255;
            let b = parseInt(result[3], 16) / 255;

            const max = Math.max(r, g, b), min = Math.min(r, g, b);
            let h = 0, s = 0, l = (max + min) / 2;

            if (max !== min) {
                const d = max - min;
                s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
                switch (max) {
                    case r: h = (g - b) / d + (g < b ? 6 : 0); break;
                    case g: h = (b - r) / d + 2; break;
                    case b: h = (r - g) / d + 4; break;
                }
                h /= 6;
            }

            return `${Math.round(h * 360)} ${Math.round(s * 100)}% ${Math.round(l * 100)}%`;
        };

        // Set CSS variables
        root.style.setProperty('--color-primary', brand.primary);
        root.style.setProperty('--color-primary-active', brand.primary_active);
        root.style.setProperty('--color-brand-accent', brand.brand_accent);
        root.style.setProperty('--color-accent-muted', brand.accent_muted);
        root.style.setProperty('--color-ink', brand.ink);

        // Set shadcn HSL variables
        const primaryHsl = hexToHSL(brand.primary);
        if (primaryHsl) {
            root.style.setProperty('--primary', primaryHsl);
            root.style.setProperty('--ring', primaryHsl);
        }

        return () => {
            // Cleanup on unmount (optional, but good practice if navigating away from landing)
            root.style.removeProperty('--color-primary');
            root.style.removeProperty('--color-primary-active');
            root.style.removeProperty('--color-brand-accent');
            root.style.removeProperty('--color-accent-muted');
            root.style.removeProperty('--color-ink');
            root.style.removeProperty('--primary');
            root.style.removeProperty('--ring');
        };
    }, [brand]);

    return null;
}
