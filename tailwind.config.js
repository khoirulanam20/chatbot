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
                canvas: '#FFFFFF',
                primary: {
                    DEFAULT: 'var(--color-primary)',
                    foreground: '#ffffff',
                },
                'primary-active': 'var(--color-primary-active)',
                'surface-soft': '#F9FAFB',
                'surface-card': '#FFFFFF',
                'surface-strong': '#E7E5E4',
                'surface-dark': '#101010',
                'surface-dark-elevated': '#1a1a1a',
                hairline: '#E7E5E4',
                'hairline-soft': '#F5F5F4',
                ink: 'var(--color-ink)',
                body: '#4B5563',
                muted: {
                    DEFAULT: '#6B7280',
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
        },
    },
    plugins: [require('tailwindcss-animate')],
};
