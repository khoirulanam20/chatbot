import { Link } from '@inertiajs/react';
import { MarketingLayout } from '@/components/marketing/MarketingLayout';
import { SeoHead } from '@/components/marketing/SeoHead';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';

export default function Privacy() {
    return (
        <MarketingLayout>
            <SeoHead
                title="Kebijakan Privasi"
                description="Kebijakan privasi AI CS Chatbot — bagaimana kami menangani data percakapan dan informasi bisnis Anda."
            />
            <div className="mx-auto max-w-3xl px-4 py-16 sm:px-6">
                <Button variant="link" asChild className="mb-6 h-auto p-0">
                    <Link href="/">← Kembali ke beranda</Link>
                </Button>
                <Card>
                    <CardHeader>
                        <CardTitle className="text-2xl">Kebijakan Privasi</CardTitle>
                        <p className="text-sm text-muted">Terakhir diperbarui: {new Date().getFullYear()}</p>
                    </CardHeader>
                    <CardContent className="prose prose-sm max-w-none space-y-6 text-body">
                        <section>
                            <h2 className="font-display text-lg font-semibold text-ink">1. Informasi yang Kami Kumpulkan</h2>
                            <p>
                                AI CS Chatbot mengumpulkan data percakapan antara pelanggan dan chatbot, dokumen knowledge base yang Anda unggah, serta informasi akun admin (nama, email) untuk operasional platform.
                            </p>
                        </section>
                        <Separator />
                        <section>
                            <h2 className="font-display text-lg font-semibold text-ink">2. Penggunaan Data</h2>
                            <p>
                                Data digunakan untuk menyediakan layanan chatbot AI, RAG retrieval, handoff ke agen, integrasi WhatsApp, dan analitik operasional. Data percakapan diproses melalui provider AI (Sumopod) sesuai konfigurasi tenant.
                            </p>
                        </section>
                        <Separator />
                        <section>
                            <h2 className="font-display text-lg font-semibold text-ink">3. Isolasi Multi-Tenant</h2>
                            <p>
                                Setiap tenant (klien) memiliki isolasi data terpisah. Data percakapan, knowledge base, dan konfigurasi tidak dapat diakses tenant lain.
                            </p>
                        </section>
                        <Separator />
                        <section>
                            <h2 className="font-display text-lg font-semibold text-ink">4. Retensi Data</h2>
                            <p>
                                Data percakapan disimpan selama akun aktif dan sesuai kebutuhan operasional. Anda dapat meminta penghapusan data dengan menghubungi tim Firstudio.
                            </p>
                        </section>
                        <Separator />
                        <section>
                            <h2 className="font-display text-lg font-semibold text-ink">5. Hak Anda</h2>
                            <p>
                                Anda berhak mengakses, memperbarui, atau meminta penghapusan data pribadi yang kami simpan. Hubungi kami melalui form kontak di landing page.
                            </p>
                        </section>
                        <Separator />
                        <section>
                            <h2 className="font-display text-lg font-semibold text-ink">6. Kontak</h2>
                            <p>
                                Pertanyaan privasi: <Link href="/#contact" className="text-ink underline">form kontak</Link> di halaman utama.
                            </p>
                        </section>
                    </CardContent>
                </Card>
            </div>
        </MarketingLayout>
    );
}
