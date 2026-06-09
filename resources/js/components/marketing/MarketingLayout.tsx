import { Link } from '@inertiajs/react';
import { Menu } from 'lucide-react';
import { ReactNode, useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';

const navLinks = [
    { href: '#kenapa-kami', label: 'Kenapa Kami' },
    { href: '#fitur', label: 'Fitur' },
    { href: '#cara-kerja', label: 'Cara Kerja' },
    { href: '#faq', label: 'FAQ' },
    { href: '#contact', label: 'Kontak' },
];

function scrollTo(href: string) {
    const id = href.replace('#', '');
    document.getElementById(id)?.scrollIntoView({ behavior: 'smooth' });
}

interface MarketingLayoutProps {
    children: ReactNode;
}

export function MarketingLayout({ children }: MarketingLayoutProps) {
    const [scrolled, setScrolled] = useState(false);

    useEffect(() => {
        const handleScroll = () => {
            setScrolled(window.scrollY > 80);
        };
        window.addEventListener('scroll', handleScroll);
        return () => window.removeEventListener('scroll', handleScroll);
    }, []);

    return (
        <div className="min-h-screen bg-canvas">
            <a
                href="#main-content"
                className="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded-md focus:bg-primary focus:px-4 focus:py-2 focus:text-on-primary"
            >
                Lewati ke konten
            </a>

            <header 
                className={`sticky top-0 z-40 transition-all duration-300 ${
                    scrolled 
                        ? 'border-b border-hairline bg-canvas/95 shadow-sm backdrop-blur supports-[backdrop-filter]:bg-canvas/80' 
                        : 'border-b-transparent bg-transparent'
                }`}
            >
                <div className="mx-auto flex h-16 max-w-6xl items-center justify-between px-4 sm:px-6">
                    <Link href="/" className="flex items-center gap-2">
                        <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-primary text-sm font-bold text-on-primary">
                            AI
                        </div>
                        <div className="flex flex-col">
                            <span className="font-display font-semibold leading-none text-ink">AI CS Chatbot</span>
                            <span className="text-[10px] font-medium text-muted">by Firstudio</span>
                        </div>
                    </Link>

                    <nav className="hidden items-center gap-1 md:flex" aria-label="Navigasi utama">
                        {navLinks.map((link) => (
                            <Button
                                key={link.href}
                                variant="ghost"
                                size="sm"
                                onClick={() => scrollTo(link.href)}
                            >
                                {link.label}
                            </Button>
                        ))}
                        <Button size="sm" className="ml-2 ring-2 ring-primary/20 hover:ring-primary/40 transition-all" >
                            <Link href="/login">Login</Link>
                        </Button>
                    </nav>

                    <Sheet>
                        <SheetTrigger asChild className="md:hidden">
                            <Button variant="outline" size="icon" aria-label="Buka menu">
                                <Menu className="h-5 w-5" />
                            </Button>
                        </SheetTrigger>
                        <SheetContent side="right">
                            <SheetHeader>
                                <SheetTitle>Menu</SheetTitle>
                            </SheetHeader>
                            <nav className="mt-6 flex flex-col gap-2">
                                {navLinks.map((link) => (
                                    <Button
                                        key={link.href}
                                        variant="ghost"
                                        className="justify-start"
                                        onClick={() => scrollTo(link.href)}
                                    >
                                        {link.label}
                                    </Button>
                                ))}
                                <Button onClick={() => scrollTo('/login')} variant="outline" className="justify-start mt-2">
                                    Login
                                </Button>
                            </nav>
                        </SheetContent>
                    </Sheet>
                </div>
            </header>

            <main id="main-content">{children}</main>

            <footer className="border-t border-hairline bg-surface-soft">
                <div className="mx-auto max-w-6xl px-4 py-12 sm:px-6">
                    <div className="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p className="font-display font-semibold text-ink">AI CS Chatbot</p>
                            <p className="mt-1 text-sm text-muted">Dikembangkan oleh Firstudio</p>
                        </div>
                        <div className="flex flex-wrap gap-4">
                            <Button variant="link" asChild className="h-auto p-0">
                                <Link href="/privacy">Kebijakan Privasi</Link>
                            </Button>
                            <Button variant="link" asChild className="h-auto p-0">
                                <Link href="/terms">Syarat & Ketentuan</Link>
                            </Button>
                            <Button variant="link" className="h-auto p-0" onClick={() => scrollTo('#contact')}>
                                Kontak
                            </Button>
                        </div>
                    </div>
                    <Separator className="my-6" />
                    <p className="text-center text-sm text-muted">
                        © {new Date().getFullYear()} Firstudio. All rights reserved.
                    </p>
                </div>
            </footer>
        </div>
    );
}
