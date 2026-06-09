import { Link } from '@inertiajs/react';
import { MarketingLayout } from '@/components/marketing/MarketingLayout';
import { SeoHead } from '@/components/marketing/SeoHead';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';

export default function Terms() {
    return (
        <MarketingLayout>
            <SeoHead
                title="Syarat & Ketentuan"
                description="Syarat dan ketentuan penggunaan layanan AI CS Chatbot oleh Firstudio."
            />
            <div className="mx-auto max-w-3xl px-4 py-16 sm:px-6">
                <Button variant="link" asChild className="mb-6 h-auto p-0">
                    <Link href="/">← Kembali ke beranda</Link>
                </Button>
                <Card>
                    <CardHeader>
                        <CardTitle className="text-2xl">Syarat & Ketentuan</CardTitle>
                        <p className="text-sm text-muted">Terakhir diperbarui: {new Date().getFullYear()}</p>
                    </CardHeader>
                    <CardContent className="prose prose-sm max-w-none space-y-6 text-body">
                        <section>
                            <h2 className="font-display text-lg font-semibold text-ink">1. Layanan</h2>
                            <p>
                                AI CS Chatbot adalah platform SaaS untuk otomasi customer service berbasis AI, termasuk RAG knowledge base, widget embed, integrasi WhatsApp, dan live handoff ke agen manusia.
                            </p>
                        </section>
                        <Separator />
                        <section>
                            <h2 className="font-display text-lg font-semibold text-ink">2. Penggunaan yang Diperbolehkan</h2>
                            <p>
                                Anda setuju menggunakan layanan hanya untuk tujuan bisnis yang sah. Dilarang menggunakan chatbot untuk spam, konten ilegal, atau aktivitas yang melanggar hukum Indonesia.
                            </p>
                        </section>
                        <Separator />
                        <section>
                            <h2 className="font-display text-lg font-semibold text-ink">3. Akurasi AI</h2>
                            <p>
                                Chatbot menjawab berdasarkan knowledge base yang Anda sediakan. Firstudio tidak menjamin 100% akurasi respons AI. Anda bertanggung jawab memverifikasi dan memperbarui konten knowledge base.
                            </p>
                        </section>
                        <Separator />
                        <section>
                            <h2 className="font-display text-lg font-semibold text-ink">4. Ketersediaan Layanan</h2>
                            <p>
                                Kami berupaya menjaga uptime layanan, namun tidak memberikan SLA uptime tertentu kecuali disepakati secara terpisah dalam kontrak enterprise.
                            </p>
                        </section>
                        <Separator />
                        <section>
                            <h2 className="font-display text-lg font-semibold text-ink">5. Batasan Tanggung Jawab</h2>
                            <p>
                                Firstudio tidak bertanggung jawab atas kerugian tidak langsung akibat penggunaan layanan, termasuk keputusan bisnis yang didasarkan pada respons chatbot AI.
                            </p>
                        </section>
                        <Separator />
                        <section>
                            <h2 className="font-display text-lg font-semibold text-ink">6. Kontak</h2>
                            <p>
                                Pertanyaan syarat layanan: <Link href="/#contact" className="text-ink underline">form kontak</Link> di halaman utama.
                            </p>
                        </section>
                    </CardContent>
                </Card>
            </div>
        </MarketingLayout>
    );
}
