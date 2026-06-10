import { Globe, MessageCircle, CheckCircle2 } from 'lucide-react';
import { useState } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { WobbleCard } from '@/components/ui/wobble-card';
import { AnimatedSectionHeader } from '../aceternity/AnimatedSectionHeader';
import { SectionShell } from '../aceternity/SectionShell';
import { channelsCopy } from '@/content/marketing';

export function ChannelsSection() {
    const [activeTab, setActiveTab] = useState<'web' | 'wa'>('web');

    return (
        <SectionShell id="channel" className="bg-canvas">
            <AnimatedSectionHeader
                eyebrow={channelsCopy.eyebrow}
                headline={channelsCopy.headline}
                subheadline={channelsCopy.subheadline}
            />

            <div className="mt-12 grid gap-8 lg:grid-cols-[1fr_2fr]">
                <div className="flex flex-col gap-4">
                    <button
                        type="button"
                        onClick={() => setActiveTab('web')}
                        className={`flex items-center gap-3 rounded-xl px-4 py-3 text-left transition-all ${
                            activeTab === 'web'
                                ? 'bg-zinc-800 ring-2 ring-primary'
                                : 'border border-white/[0.1] text-muted hover:text-on-dark'
                        }`}
                    >
                        <Globe className={`h-5 w-5 ${activeTab === 'web' ? 'text-primary' : ''}`} />
                        <span className="font-medium">Widget Website</span>
                    </button>
                    <button
                        type="button"
                        onClick={() => setActiveTab('wa')}
                        className={`flex items-center gap-3 rounded-xl px-4 py-3 text-left transition-all ${
                            activeTab === 'wa'
                                ? 'bg-zinc-800 ring-2 ring-success'
                                : 'border border-white/[0.1] text-muted hover:text-on-dark'
                        }`}
                    >
                        <MessageCircle className={`h-5 w-5 ${activeTab === 'wa' ? 'text-success' : ''}`} />
                        <span className="font-medium">WhatsApp Bisnis</span>
                    </button>

                    <ul className="mt-4 space-y-3 text-sm text-muted">
                        {channelsCopy.features.map((feature, i) => (
                            <li key={i} className="flex items-center gap-2">
                                <CheckCircle2 className="h-4 w-4 text-primary" />
                                {feature}
                            </li>
                        ))}
                    </ul>
                </div>

                <WobbleCard>
                    <div className="p-6 sm:p-10">
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
                                        <h3 className="text-lg font-semibold text-on-dark">Embed di Website Anda</h3>
                                        <p className="text-sm text-muted">Satu baris script, widget langsung aktif.</p>
                                    </div>
                                    <div className="overflow-hidden rounded-xl border border-white/[0.1] bg-zinc-900 shadow-xl">
                                        <div className="flex items-center gap-2 border-b border-hairline bg-zinc-800 px-4 py-3">
                                            <div className="flex gap-1.5">
                                                <span className="h-2.5 w-2.5 rounded-full bg-error/60" />
                                                <span className="h-2.5 w-2.5 rounded-full bg-warning/60" />
                                                <span className="h-2.5 w-2.5 rounded-full bg-success/60" />
                                            </div>
                                        </div>
                                        <div className="relative h-64 bg-zinc-900 p-4">
                                            <div className="absolute bottom-4 right-4 w-64 overflow-hidden rounded-lg border border-white/[0.1] shadow-lg">
                                                <div className="bg-primary px-3 py-2 text-xs font-medium text-on-primary">CS Assistant</div>
                                                <div className="space-y-2 bg-zinc-800 p-3">
                                                    <div className="w-fit max-w-[80%] rounded-lg rounded-tl-none bg-zinc-900 px-3 py-2 text-xs text-on-dark-soft">
                                                        Halo, ada yang bisa dibantu?
                                                    </div>
                                                    <div className="ml-auto w-fit max-w-[80%] rounded-lg rounded-tr-none bg-primary px-3 py-2 text-xs text-on-primary">
                                                        Saya mau tanya soal pengiriman
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
                                        <h3 className="text-lg font-semibold text-on-dark">Connect WhatsApp Business</h3>
                                        <p className="text-sm text-muted">Scan QR, chatbot langsung aktif merespons.</p>
                                    </div>
                                    <div className="overflow-hidden rounded-xl border border-white/[0.1] bg-[#0b141a] shadow-xl">
                                        <div className="flex items-center gap-3 bg-[#00a884] px-4 py-3 text-white">
                                            <div className="flex h-8 w-8 items-center justify-center rounded-full bg-white/20">
                                                <MessageCircle className="h-4 w-4" />
                                            </div>
                                            <div>
                                                <div className="text-sm font-medium">CS Toko Online</div>
                                                <div className="text-[10px] opacity-80">Online</div>
                                            </div>
                                        </div>
                                        <div className="h-64 space-y-2 overflow-y-auto p-4">
                                            <div className="w-fit max-w-[85%] rounded-lg rounded-tl-none bg-[#1f2c34] px-3 py-1.5 text-xs text-on-dark">
                                                Halo kak!
                                            </div>
                                            <div className="w-fit max-w-[85%] rounded-lg rounded-tl-none bg-[#1f2c34] px-3 py-1.5 text-xs text-on-dark">
                                                Pesanan #12345 sudah dikirim ya
                                            </div>
                                            <div className="ml-auto w-fit max-w-[85%] rounded-lg rounded-tr-none bg-[#005c4b] px-3 py-1.5 text-xs text-on-dark">
                                                Oke makasih min
                                            </div>
                                        </div>
                                    </div>
                                </motion.div>
                            )}
                        </AnimatePresence>
                    </div>
                </WobbleCard>
            </div>
        </SectionShell>
    );
}
