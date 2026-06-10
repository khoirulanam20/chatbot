import { FormEventHandler } from 'react';
import { useForm } from '@inertiajs/react';
import { MessageCircle, ArrowRight, Zap, Shield, Clock } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { LampContainer } from '@/components/ui/lamp';
import { MovingBorderButton } from '@/components/ui/moving-border';
import { contactCopy } from '@/content/marketing';

interface ContactSectionProps {
    contactWhatsApp: string;
}

function buildWhatsAppUrl(baseUrl: string, text: string): string {
    try {
        const url = new URL(baseUrl);
        url.searchParams.set('text', text);
        return url.toString();
    } catch {
        return baseUrl;
    }
}

export function ContactSection({ contactWhatsApp }: ContactSectionProps) {
    const { data, setData, reset } = useForm({
        name: '',
        email: '',
        company: '',
        message: '',
        website: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        if (data.website) return;

        const text = `Halo, saya ingin permintaan demo AI CS Chatbot.

Nama: ${data.name}
Email: ${data.email}
Perusahaan: ${data.company}
Kebutuhan: ${data.message}`;

        const waUrl = buildWhatsAppUrl(contactWhatsApp, text);
        window.open(waUrl, '_blank');
        reset('name', 'email', 'company', 'message');
    };

    return (
        <section id="contact" className="relative">
            <LampContainer className="min-h-0 py-20 sm:py-32">
                <div className="relative z-50 w-full max-w-6xl px-4 sm:px-6">
                    <div className="grid gap-12 lg:grid-cols-2 lg:gap-8">
                        <div>
                            <span className="mb-3 inline-block rounded-pill border border-white/[0.1] bg-black/40 px-3 py-1 text-xs font-semibold tracking-widest text-muted uppercase">
                                {contactCopy.eyebrow}
                            </span>
                            <h2 className="font-display text-3xl font-bold text-on-dark sm:text-4xl lg:text-5xl">
                                {contactCopy.headline}
                            </h2>
                            <p className="mt-4 text-lg text-on-dark-soft">
                                {contactCopy.subheadline}
                            </p>

                            <div className="mt-8 space-y-4">
                                {contactCopy.bullets.map((bullet, i) => (
                                    <div key={i} className="flex items-start gap-3">
                                        <div className="mt-1 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary/20 text-primary">
                                            {bullet.icon === 'Zap' && <Zap className="h-4 w-4" />}
                                            {bullet.icon === 'Shield' && <Shield className="h-4 w-4" />}
                                            {bullet.icon === 'Clock' && <Clock className="h-4 w-4" />}
                                        </div>
                                        <div>
                                            <h4 className="font-semibold text-on-dark">{bullet.title}</h4>
                                            <p className="text-sm text-muted">{bullet.desc}</p>
                                        </div>
                                    </div>
                                ))}
                            </div>

                            <div className="mt-10 rounded-xl border border-white/[0.1] bg-black/40 p-6 backdrop-blur-sm">
                                <h4 className="font-semibold text-on-dark">Butuh respons cepat?</h4>
                                <p className="mt-1 text-sm text-muted">Tim kami siap membantu Anda via WhatsApp.</p>
                                <Button className="mt-4 w-full sm:w-auto" asChild>
                                    <a href={contactWhatsApp} target="_blank" rel="noopener noreferrer">
                                        <MessageCircle className="mr-2 h-4 w-4" />
                                        Chat via WhatsApp
                                    </a>
                                </Button>
                            </div>
                        </div>

                        <div className="rounded-xl border border-white/[0.1] bg-black/60 p-6 backdrop-blur-md sm:p-8">
                            <h3 className="font-display text-xl font-semibold text-on-dark">{contactCopy.form.title}</h3>
                            <p className="mt-1 text-sm text-muted">{contactCopy.form.description}</p>

                            <form onSubmit={submit} className="mt-6 space-y-4">
                                <input
                                    type="text"
                                    name="website"
                                    value={data.website}
                                    onChange={(e) => setData('website', e.target.value)}
                                    className="hidden"
                                    tabIndex={-1}
                                    autoComplete="off"
                                />
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <Label htmlFor="name" className="text-on-dark-soft">Nama Lengkap</Label>
                                        <Input
                                            id="name"
                                            placeholder="Budi Santoso"
                                            value={data.name}
                                            onChange={(e) => setData('name', e.target.value)}
                                            className="mt-1.5 border-white/[0.1] bg-zinc-900 text-on-dark"
                                            required
                                        />
                                    </div>
                                    <div>
                                        <Label htmlFor="email" className="text-on-dark-soft">Email Kerja</Label>
                                        <Input
                                            id="email"
                                            type="email"
                                            placeholder="budi@perusahaan.com"
                                            value={data.email}
                                            onChange={(e) => setData('email', e.target.value)}
                                            className="mt-1.5 border-white/[0.1] bg-zinc-900 text-on-dark"
                                            required
                                        />
                                    </div>
                                </div>
                                <div>
                                    <Label htmlFor="company" className="text-on-dark-soft">Nama Perusahaan</Label>
                                    <Input
                                        id="company"
                                        placeholder="PT Maju Bersama"
                                        value={data.company}
                                        onChange={(e) => setData('company', e.target.value)}
                                        className="mt-1.5 border-white/[0.1] bg-zinc-900 text-on-dark"
                                        required
                                    />
                                </div>
                                <div>
                                    <Label htmlFor="message" className="text-on-dark-soft">Pesan / Kebutuhan</Label>
                                    <Textarea
                                        id="message"
                                        placeholder="Kami butuh chatbot untuk website e-commerce kami..."
                                        value={data.message}
                                        onChange={(e) => setData('message', e.target.value)}
                                        className="mt-1.5 resize-none border-white/[0.1] bg-zinc-900 text-on-dark"
                                        rows={4}
                                        required
                                    />
                                </div>
                                <MovingBorderButton
                                    type="submit"
                                    borderRadius="0.75rem"
                                    containerClassName="h-12 w-full"
                                    className="w-full border-slate-800 bg-slate-900/90 text-sm font-semibold text-on-dark"
                                >
                                    {contactCopy.form.submit}
                                    <ArrowRight className="ml-2 inline h-4 w-4" />
                                </MovingBorderButton>
                            </form>
                        </div>
                    </div>
                </div>
            </LampContainer>
        </section>
    );
}
