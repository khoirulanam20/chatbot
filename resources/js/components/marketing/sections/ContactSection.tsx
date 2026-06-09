import { FormEventHandler } from 'react';
import { useForm } from '@inertiajs/react';
import { MessageCircle, ArrowRight, Zap, Shield, Clock } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { AnimateInView } from '../motion/AnimateInView';
import { contactCopy } from '@/content/marketing';

interface ContactSectionProps {
    contactWhatsApp: string;
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
        
        // Honeypot check
        if (data.website) return;

        const text = `Halo, saya ingin permintaan demo AI CS Chatbot.

Nama: ${data.name}
Email: ${data.email}
Perusahaan: ${data.company}
Kebutuhan: ${data.message}`;

        const waUrl = `https://wa.me/6285117494221?text=${encodeURIComponent(text)}`;
        window.open(waUrl, '_blank');
        reset('name', 'email', 'company', 'message');
    };

    return (
        <section id="contact" className="border-t border-hairline bg-surface-soft py-20 sm:py-32">
            <div className="mx-auto max-w-6xl px-4 sm:px-6">
                <div className="grid gap-12 lg:grid-cols-2 lg:gap-8">
                    <AnimateInView>
                        <span className="mb-3 inline-block rounded-pill border border-hairline bg-canvas px-3 py-1 text-xs font-semibold tracking-widest text-muted uppercase shadow-sm">
                            {contactCopy.eyebrow}
                        </span>
                        <h2 className="font-display text-3xl font-bold text-ink sm:text-4xl lg:text-5xl">
                            {contactCopy.headline}
                        </h2>
                        <p className="mt-4 text-lg text-muted">
                            {contactCopy.subheadline}
                        </p>

                        <div className="mt-8 space-y-4">
                            {contactCopy.bullets.map((bullet, i) => (
                                <div key={i} className="flex items-start gap-3">
                                    <div className="mt-1 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-accent-muted text-primary">
                                        {bullet.icon === 'Zap' && <Zap className="h-4 w-4" />}
                                        {bullet.icon === 'Shield' && <Shield className="h-4 w-4" />}
                                        {bullet.icon === 'Clock' && <Clock className="h-4 w-4" />}
                                    </div>
                                    <div>
                                        <h4 className="font-semibold text-ink">{bullet.title}</h4>
                                        <p className="text-sm text-muted">{bullet.desc}</p>
                                    </div>
                                </div>
                            ))}
                        </div>

                        <div className="mt-10 rounded-xl border border-hairline bg-canvas p-6 shadow-sm">
                            <h4 className="font-semibold text-ink">Butuh respons cepat?</h4>
                            <p className="mt-1 text-sm text-muted">Tim kami siap membantu Anda via WhatsApp.</p>
                            <Button className="mt-4 w-full sm:w-auto" asChild>
                                <a href={contactWhatsApp} target="_blank" rel="noopener noreferrer">
                                    <MessageCircle className="mr-2 h-4 w-4" />
                                    Chat via WhatsApp
                                </a>
                            </Button>
                        </div>
                    </AnimateInView>

                    <AnimateInView>
                        <Card className="shadow-lg">
                            <CardHeader>
                                <CardTitle>{contactCopy.form.title}</CardTitle>
                                <CardDescription>{contactCopy.form.description}</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <form onSubmit={submit} className="space-y-4">
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
                                            <Label htmlFor="name">Nama Lengkap</Label>
                                            <Input
                                                id="name"
                                                placeholder="Budi Santoso"
                                                value={data.name}
                                                onChange={(e) => setData('name', e.target.value)}
                                                className="mt-1.5"
                                                required
                                            />
                                        </div>
                                        <div>
                                            <Label htmlFor="email">Email Kerja</Label>
                                            <Input
                                                id="email"
                                                type="email"
                                                placeholder="budi@perusahaan.com"
                                                value={data.email}
                                                onChange={(e) => setData('email', e.target.value)}
                                                className="mt-1.5"
                                                required
                                            />
                                        </div>
                                    </div>
                                    <div>
                                        <Label htmlFor="company">Nama Perusahaan</Label>
                                        <Input
                                            id="company"
                                            placeholder="PT Maju Bersama"
                                            value={data.company}
                                            onChange={(e) => setData('company', e.target.value)}
                                            className="mt-1.5"
                                            required
                                        />
                                    </div>
                                    <div>
                                        <Label htmlFor="message">Pesan / Kebutuhan</Label>
                                        <Textarea
                                            id="message"
                                            placeholder="Kami butuh chatbot untuk website e-commerce kami..."
                                            value={data.message}
                                            onChange={(e) => setData('message', e.target.value)}
                                            className="mt-1.5 resize-none"
                                            rows={4}
                                            required
                                        />
                                    </div>
                                    <Button type="submit" size="lg" className="w-full">
                                        {contactCopy.form.submit}
                                        <ArrowRight className="ml-2 h-4 w-4" />
                                    </Button>
                                </form>
                            </CardContent>
                        </Card>
                    </AnimateInView>
                </div>
            </div>
        </section>
    );
}
