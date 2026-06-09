import { Globe, MessageCircle, CheckCircle2 } from 'lucide-react';
import { Card } from '@/components/ui/card';
import { AnimateInView } from '../motion/AnimateInView';
import { useState } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { SectionHeader } from '../SectionHeader';
import { channelsCopy } from '@/content/marketing';

export function ChannelsSection() {
    const [activeTab, setActiveTab] = useState<'web' | 'wa'>('web');

    return (
        <section id="channel" className="py-20 sm:py-32">
            <div className="mx-auto max-w-6xl px-4 sm:px-6">
                <SectionHeader 
                    eyebrow={channelsCopy.eyebrow}
                    headline={channelsCopy.headline}
                    subheadline={channelsCopy.subheadline}
                />

                <AnimateInView className="mt-12">
                    <Card className="overflow-hidden border-hairline shadow-lg">
                        <div className="flex flex-col lg:flex-row">
                            {/* Sidebar / Tabs */}
                            <div className="flex flex-col border-b border-hairline bg-surface-soft p-6 lg:w-1/3 lg:border-b-0 lg:border-r">
                                <div className="flex gap-2 lg:flex-col">
                                    <button
                                        onClick={() => setActiveTab('web')}
                                        className={`flex flex-1 items-center gap-3 rounded-lg px-4 py-3 text-left transition-colors ${
                                            activeTab === 'web'
                                                ? 'bg-canvas shadow-sm ring-1 ring-hairline'
                                                : 'text-muted hover:bg-canvas/50 hover:text-ink'
                                        }`}
                                    >
                                        <Globe className={`h-5 w-5 ${activeTab === 'web' ? 'text-primary' : ''}`} />
                                        <span className="font-medium">Widget Website</span>
                                    </button>
                                    <button
                                        onClick={() => setActiveTab('wa')}
                                        className={`flex flex-1 items-center gap-3 rounded-lg px-4 py-3 text-left transition-colors ${
                                            activeTab === 'wa'
                                                ? 'bg-canvas shadow-sm ring-1 ring-hairline'
                                                : 'text-muted hover:bg-canvas/50 hover:text-ink'
                                        }`}
                                    >
                                        <MessageCircle className={`h-5 w-5 ${activeTab === 'wa' ? 'text-success' : ''}`} />
                                        <span className="font-medium">WhatsApp Bisnis</span>
                                    </button>
                                </div>

                                <div className="mt-8 hidden lg:block">
                                    <h4 className="mb-4 text-sm font-semibold text-ink">Fitur Channel:</h4>
                                    <ul className="space-y-3 text-sm text-muted">
                                        {channelsCopy.features.map((feature, i) => (
                                            <li key={i} className="flex items-center gap-2">
                                                <CheckCircle2 className="h-4 w-4 text-primary" />
                                                {feature}
                                            </li>
                                        ))}
                                    </ul>
                                </div>
                            </div>

                            {/* Content Area */}
                            <div className="relative flex-1 bg-surface-card p-6 sm:p-10">
                                <AnimatePresence mode="wait">
                                    {activeTab === 'web' ? (
                                        <motion.div
                                            key="web"
                                            initial={{ opacity: 0, y: 10 }}
                                            animate={{ opacity: 1, y: 0 }}
                                            exit={{ opacity: 0, y: -10 }}
                                            transition={{ duration: 0.3 }}
                                            className="mx-auto max-w-sm"
                                        >
                                            <div className="mb-6 text-center">
                                                <h3 className="text-lg font-semibold text-ink">Embed di Website Anda</h3>
                                                <p className="text-sm text-muted">Satu baris script, widget langsung aktif.</p>
                                            </div>
                                            <div className="overflow-hidden rounded-xl border border-hairline bg-canvas shadow-xl">
                                                <div className="flex items-center gap-2 border-b border-hairline bg-surface-soft px-4 py-3">
                                                    <div className="flex gap-1.5">
                                                        <span className="h-2.5 w-2.5 rounded-full bg-error/60" />
                                                        <span className="h-2.5 w-2.5 rounded-full bg-warning/60" />
                                                        <span className="h-2.5 w-2.5 rounded-full bg-success/60" />
                                                    </div>
                                                </div>
                                                <div className="h-64 bg-canvas p-4 relative">
                                                    <div className="absolute bottom-4 right-4 w-64 rounded-lg border border-hairline shadow-lg overflow-hidden">
                                                        <div className="bg-primary px-3 py-2 text-xs font-medium text-on-primary">CS Assistant</div>
                                                        <div className="p-3 bg-surface-soft space-y-2">
                                                            <div className="w-fit max-w-[80%] rounded-lg rounded-tl-none bg-canvas px-3 py-2 text-xs shadow-sm">
                                                                Halo, ada yang bisa dibantu?
                                                            </div>
                                                            <div className="ml-auto w-fit max-w-[80%] rounded-lg rounded-tr-none bg-primary px-3 py-2 text-xs text-on-primary shadow-sm">
                                                                Saya mau tanya soal pengiriman
                                                            </div>
                                                            <div className="w-fit max-w-[80%] rounded-lg rounded-tl-none bg-canvas px-3 py-2 text-xs shadow-sm">
                                                                Tentu! Pengiriman reguler 2-3 hari kerja 📦
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </motion.div>
                                    ) : (
                                        <motion.div
                                            key="wa"
                                            initial={{ opacity: 0, y: 10 }}
                                            animate={{ opacity: 1, y: 0 }}
                                            exit={{ opacity: 0, y: -10 }}
                                            transition={{ duration: 0.3 }}
                                            className="mx-auto max-w-sm"
                                        >
                                            <div className="mb-6 text-center">
                                                <h3 className="text-lg font-semibold text-ink">Connect WhatsApp Business</h3>
                                                <p className="text-sm text-muted">Scan QR, chatbot langsung aktif merespons.</p>
                                            </div>
                                            <div className="overflow-hidden rounded-xl border border-hairline bg-[#efeae2] shadow-xl">
                                                <div className="flex items-center gap-3 bg-[#00a884] px-4 py-3 text-white">
                                                    <div className="flex h-8 w-8 items-center justify-center rounded-full bg-white/20">
                                                        <MessageCircle className="h-4 w-4" />
                                                    </div>
                                                    <div>
                                                        <div className="text-sm font-medium">CS Toko Online</div>
                                                        <div className="text-[10px] opacity-80">Online</div>
                                                    </div>
                                                </div>
                                                <div className="h-64 p-4 space-y-2 overflow-y-auto" style={{ backgroundImage: 'url("https://static.whatsapp.net/rsrc.php/v3/yl/r/gi_DckOUM5a.png")', backgroundSize: 'cover' }}>
                                                    <div className="w-fit max-w-[85%] rounded-lg rounded-tl-none bg-white px-3 py-1.5 text-xs shadow-sm">
                                                        Halo kak!
                                                        <div className="text-right text-[9px] text-gray-400 mt-1">08:23</div>
                                                    </div>
                                                    <div className="w-fit max-w-[85%] rounded-lg rounded-tl-none bg-white px-3 py-1.5 text-xs shadow-sm">
                                                        Pesanan #12345 sudah dikirim ya 🚚
                                                        <div className="text-right text-[9px] text-gray-400 mt-1">08:23</div>
                                                    </div>
                                                    <div className="w-fit max-w-[85%] rounded-lg rounded-tl-none bg-white px-3 py-1.5 text-xs shadow-sm">
                                                        Estimasi sampai besok pagi
                                                        <div className="text-right text-[9px] text-gray-400 mt-1">08:24</div>
                                                    </div>
                                                    <div className="ml-auto w-fit max-w-[85%] rounded-lg rounded-tr-none bg-[#d9fdd3] px-3 py-1.5 text-xs shadow-sm">
                                                        Oke makasih min
                                                        <div className="text-right text-[9px] text-gray-400 mt-1">08:25</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </motion.div>
                                    )}
                                </AnimatePresence>
                            </div>
                        </div>
                    </Card>
                </AnimateInView>
            </div>
        </section>
    );
}
