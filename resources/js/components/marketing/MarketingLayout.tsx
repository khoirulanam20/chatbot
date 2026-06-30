import { Link } from '@inertiajs/react';
import { Menu } from 'lucide-react';
import { ReactNode, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { FloatingNav } from '@/components/ui/floating-navbar';
import { useScrollTo } from './aceternity/useScrollTo';

const navLinks = [
    { href: '#kenapa-kami', label: 'Kenapa Kami' },
    { href: '#masalah', label: 'Masalah' },
    { href: '#fitur', label: 'Fitur' },
    { href: '#cara-kerja', label: 'Cara Kerja' },
    { href: '#use-case', label: 'Use Case' },
    { href: '#faq', label: 'FAQ' },
    { href: '#contact', label: 'Kontak' },
];

interface MarketingLayoutProps {
    children: ReactNode;
}

export function MarketingLayout({ children }: MarketingLayoutProps) {
    const scrollTo = useScrollTo();

    useEffect(() => {
        document.body.classList.add('theme-light');

        return () => {
            document.body.classList.remove('theme-light');
        };
    }, []);

    const floatingNavItems = navLinks.map((link) => ({
        name: link.label,
        link: link.href,
    }));

    return (
        <div className="marketing-theme-light min-h-screen bg-canvas text-ink">
            <a
                href="#main-content"
                className="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-[6000] focus:rounded-md focus:bg-primary focus:px-4 focus:py-2 focus:text-on-primary"
            >
                Lewati ke konten
            </a>

            <div className="fixed left-4 top-4 z-[5000] md:left-6">
                <Link href="/" className="flex items-center gap-2">
                    <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-primary text-sm font-bold text-on-primary">
                        AI
                    </div>
                    <div className="hidden flex-col sm:flex">
                        <span className="font-display font-semibold leading-none text-on-dark">AI CS Chatbot</span>
                        <span className="text-[10px] font-medium text-muted">by Firstudio</span>
                    </div>
                </Link>
            </div>

            <FloatingNav navItems={floatingNavItems} />

            <div className="fixed right-4 top-4 z-[5000] hidden items-center gap-2 md:flex">
                <Button
                    size="sm"
                    variant="outline"
                    className="border-white/[0.2] bg-black/50 text-on-dark backdrop-blur-md hover:bg-black/70"
                    onClick={() => scrollTo('contact')}
                >
                    Demo Gratis
                </Button>
                <Button size="sm" asChild>
                    <Link href="/login">Login</Link>
                </Button>
            </div>

            <Sheet>
                <SheetTrigger asChild className="fixed right-4 top-4 z-[5000] md:hidden">
                    <Button
                        variant="outline"
                        size="icon"
                        aria-label="Buka menu"
                        className="border-white/[0.2] bg-black/50 backdrop-blur-md"
                    >
                        <Menu className="h-5 w-5" />
                    </Button>
                </SheetTrigger>
                <SheetContent side="right" className="bg-zinc-900 border-hairline">
                    <SheetHeader>
                        <SheetTitle className="text-on-dark">Menu</SheetTitle>
                    </SheetHeader>
                    <nav className="mt-6 flex flex-col gap-2">
                        {navLinks.map((link) => (
                            <Button
                                key={link.href}
                                variant="ghost"
                                className="justify-start text-on-dark"
                                onClick={() => scrollTo(link.href.replace('#', ''))}
                            >
                                {link.label}
                            </Button>
                        ))}
                        <Button variant="outline" className="mt-2 justify-start" asChild>
                            <Link href="/login">Login</Link>
                        </Button>
                    </nav>
                </SheetContent>
            </Sheet>

            <main id="main-content" className="pt-20">{children}</main>

            <footer className="border-t border-hairline bg-surface-soft">
                <div className="mx-auto max-w-6xl px-4 py-12 sm:px-6">
                    <div className="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p className="font-display font-semibold text-on-dark">AI CS Chatbot</p>
                            <p className="mt-1 text-sm text-muted">Dikembangkan oleh Firstudio</p>
                        </div>
                        <div className="flex flex-wrap gap-4">
                            <Button variant="link" asChild className="h-auto p-0 text-on-dark-soft">
                                <Link href="/privacy">Kebijakan Privasi</Link>
                            </Button>
                            <Button variant="link" asChild className="h-auto p-0 text-on-dark-soft">
                                <Link href="/terms">Syarat & Ketentuan</Link>
                            </Button>
                            <Button variant="link" className="h-auto p-0 text-on-dark-soft" onClick={() => scrollTo('contact')}>
                                Kontak
                            </Button>
                        </div>
                    </div>
                    <Separator className="my-6 bg-hairline" />
                    <p className="text-center text-sm text-muted">
                        © {new Date().getFullYear()} Firstudio. All rights reserved.
                    </p>
                </div>
            </footer>
        </div>
    );
}
