import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: ['class'],
    content: [
        './resources/js/**/*.{ts,tsx}',
        './resources/views/app.blade.php',
    ],
    theme: {
        extend: {
            colors: {
                canvas: '#0a0a0a',
                primary: {
                    DEFAULT: 'var(--color-primary)',
                    foreground: '#ffffff',
                },
                'primary-active': 'var(--color-primary-active)',
                'surface-soft': '#111111',
                'surface-card': '#1a1a1a',
                'surface-strong': '#27272a',
                'surface-dark': '#000000',
                'surface-dark-elevated': '#1a1a1a',
                hairline: '#27272a',
                'hairline-soft': '#3f3f46',
                ink: '#fafafa',
                body: '#d4d4d8',
                muted: {
                    DEFAULT: '#a1a1aa',
                    foreground: '#ffffff',
                },
                'muted-soft': '#898989',
                'on-primary': '#ffffff',
                'on-dark': '#ffffff',
                'on-dark-soft': '#a1a1aa',
                'brand-accent': 'var(--color-brand-accent)',
                'accent-muted': 'var(--color-accent-muted)',
                'accent-subtle': '#C7D2FE',
                success: '#10b981',
                warning: '#f59e0b',
                error: '#ef4444',
                border: 'hsl(var(--border))',
                input: 'hsl(var(--input))',
                ring: 'hsl(var(--ring))',
                background: 'hsl(var(--background))',
                foreground: 'hsl(var(--foreground))',
                secondary: {
                    DEFAULT: 'hsl(var(--secondary))',
                    foreground: 'hsl(var(--secondary-foreground))',
                },
                destructive: {
                    DEFAULT: 'hsl(var(--destructive))',
                    foreground: 'hsl(var(--destructive-foreground))',
                },
                accent: {
                    DEFAULT: 'hsl(var(--accent))',
                    foreground: 'hsl(var(--accent-foreground))',
                },
                popover: {
                    DEFAULT: 'hsl(var(--popover))',
                    foreground: 'hsl(var(--popover-foreground))',
                },
                card: {
                    DEFAULT: 'hsl(var(--card))',
                    foreground: 'hsl(var(--card-foreground))',
                },
            },
            fontFamily: {
                display: ['"Plus Jakarta Sans"', ...defaultTheme.fontFamily.sans],
                body: ['Inter', ...defaultTheme.fontFamily.sans],
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            borderRadius: {
                xs: '4px',
                sm: 'calc(var(--radius) - 4px)',
                md: 'calc(var(--radius) - 2px)',
                lg: 'var(--radius)',
                xl: '16px',
                pill: '9999px',
            },
            spacing: {
                xxs: '4px',
                xs: '8px',
                sm: '12px',
                md: '16px',
                lg: '24px',
                xl: '32px',
                xxl: '48px',
                section: '96px',
            },
            animation: {
                aurora: 'aurora 60s linear infinite',
                spotlight: 'spotlight 2s ease .75s 1 forwards',
                scroll: 'scroll var(--animation-duration, 40s) var(--animation-direction, forwards) linear infinite',
                'fade-in': 'fade-in 0.5s ease-out forwards',
            },
            keyframes: {
                aurora: {
                    from: { backgroundPosition: '50% 50%, 50% 50%' },
                    to: { backgroundPosition: '350% 50%, 350% 50%' },
                },
                spotlight: {
                    '0%': { opacity: 0, transform: 'translate(-72%, -62%) scale(0.5)' },
                    '100%': { opacity: 1, transform: 'translate(-50%,-40%) scale(1)' },
                },
                scroll: {
                    to: { transform: 'translate(calc(-50% - 0.5rem))' },
                },
                'fade-in': {
                    from: { opacity: 0 },
                    to: { opacity: 1 },
                },
            },
        },
    },
    plugins: [require('tailwindcss-animate')],
};
