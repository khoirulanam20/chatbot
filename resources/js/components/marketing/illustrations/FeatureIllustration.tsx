import { motion, useInView } from 'framer-motion';
import { RotateCcw } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { useReducedMotion } from '../motion/useReducedMotion';

export type FeatureType = 'rag' | 'widget' | 'whatsapp' | 'handoff';

interface FeatureIllustrationProps {
    type: FeatureType;
    variant?: 'card' | 'showcase';
    forceActive?: boolean;
}

export function FeatureIllustration({ type, variant = 'card', forceActive = false }: FeatureIllustrationProps) {
    const ref = useRef(null);
    const inView = useInView(ref, { once: false, margin: '-40px' });
    const reduced = useReducedMotion();
    const [key, setKey] = useState(0);

    const active = forceActive || (inView && !reduced);
    const isShowcase = variant === 'showcase';
    const heightClass = isShowcase ? 'h-64 sm:h-72' : 'h-52';

    const handleReplay = () => setKey((k) => k + 1);

    return (
        <div
            ref={ref}
            className={`group relative flex ${heightClass} items-center justify-center overflow-hidden rounded-xl bg-gradient-to-br from-zinc-900 to-zinc-800 p-4 ${
                isShowcase ? '' : 'mb-6'
            }`}
        >
            {!isShowcase && (
                <motion.button
                    type="button"
                    whileTap={{ scale: 0.9 }}
                    onClick={handleReplay}
                    className="absolute right-2 top-2 z-10 flex h-6 w-6 items-center justify-center rounded-md bg-zinc-800/80 text-muted opacity-0 shadow-sm backdrop-blur transition-opacity hover:text-on-dark group-hover:opacity-100"
                    title="Putar Ulang"
                >
                    <RotateCcw className="h-3 w-3" />
                </motion.button>
            )}

            <div key={key} className="h-full w-full">
                {type === 'rag' && <RagAnimation active={active} interactive={isShowcase} />}
                {type === 'widget' && <WidgetAnimation active={active} interactive={isShowcase} />}
                {type === 'whatsapp' && <WhatsAppAnimation active={active} interactive={isShowcase} />}
                {type === 'handoff' && <HandoffFeatureAnimation active={active} interactive={isShowcase} />}
            </div>
        </div>
    );
}

function RagAnimation({ active, interactive }: { active: boolean; interactive: boolean }) {
    const [scanned, setScanned] = useState(false);
    const [answered, setAnswered] = useState(false);

    const showScan = scanned;
    const showAnswer = answered;

    useEffect(() => {
        if (!active) {
            setScanned(false);
            setAnswered(false);
            return;
        }
        if (interactive) return;

        const t1 = setTimeout(() => setScanned(true), 1200);
        const t2 = setTimeout(() => setAnswered(true), 2000);
        return () => {
            clearTimeout(t1);
            clearTimeout(t2);
        };
    }, [active, interactive]);

    const handleScan = () => {
        if (scanned) return;
        setScanned(true);
        setTimeout(() => setAnswered(true), 800);
    };

    return (
        <div className="relative flex h-full w-full items-center justify-center gap-4 sm:gap-6">
            <motion.button
                type="button"
                disabled={!interactive || scanned}
                onClick={handleScan}
                className={`flex w-24 flex-col gap-2 rounded-lg border bg-zinc-900 p-2 shadow-sm transition-all ${
                    interactive && !scanned
                        ? 'cursor-pointer border-primary/40 hover:border-primary hover:shadow-md'
                        : 'cursor-default border-hairline'
                }`}
                initial={{ x: -20, opacity: 0 }}
                animate={active ? { x: 0, opacity: 1 } : {}}
                transition={{ duration: 0.5 }}
                whileTap={interactive && !scanned ? { scale: 0.97 } : {}}
            >
                <div className="flex items-center gap-1.5 border-b border-hairline pb-1">
                    <span className="text-[10px]">📄</span>
                    <span className="text-[8px] font-medium text-on-dark">Kebijakan.pdf</span>
                </div>
                {[0, 1, 2].map((i) => (
                    <motion.div
                        key={i}
                        className="h-1.5 rounded bg-surface-strong"
                        initial={{ scaleX: 0 }}
                        animate={showScan ? { scaleX: 1 } : {}}
                        transition={{ delay: interactive ? i * 0.15 : 0.5 + i * 0.2, duration: 0.4 }}
                        style={{ originX: 0, width: `${100 - i * 15}%` }}
                    />
                ))}
                {interactive && !scanned && (
                    <span className="text-center text-[8px] font-medium text-primary">Klik untuk scan</span>
                )}
            </motion.button>

            <motion.div
                className="flex flex-col gap-1"
                initial={{ opacity: 0 }}
                animate={showScan ? { opacity: 1 } : {}}
                transition={{ delay: interactive ? 0 : 1.2 }}
            >
                {[0, 1, 2].map((i) => (
                    <motion.div
                        key={i}
                        className="h-1 w-8 rounded-full bg-primary/40"
                        animate={showScan ? { x: [0, 10, 0], opacity: [0.5, 1, 0.5] } : {}}
                        transition={{ duration: 1.5, repeat: Infinity, delay: i * 0.2 }}
                    />
                ))}
            </motion.div>

            <motion.div
                className="w-32 rounded-lg border border-hairline bg-zinc-900 p-2 shadow-md sm:w-36"
                initial={{ x: 20, opacity: 0 }}
                animate={showAnswer ? { x: 0, opacity: 1 } : { opacity: 0.3 }}
                transition={{ delay: interactive ? 0 : 1.5, duration: 0.5 }}
            >
                <div className="mb-2 rounded bg-zinc-800 px-2 py-1 text-[8px] text-muted">
                    Apa kebijakan retur?
                </div>
                {showAnswer ? (
                    <div className="rounded bg-primary px-2 py-1.5 text-[9px] leading-relaxed text-on-primary">
                        Retur maksimal <span className="bg-white/20 px-0.5 font-semibold">7 hari</span> dengan struk asli.
                    </div>
                ) : (
                    <div className="flex items-center gap-1 rounded bg-zinc-800 px-2 py-1.5">
                        {[0, 1, 2].map((i) => (
                            <motion.span
                                key={i}
                                className="h-1 w-1 rounded-full bg-muted"
                                animate={{ opacity: [0.3, 1, 0.3] }}
                                transition={{ duration: 0.8, repeat: Infinity, delay: i * 0.2 }}
                            />
                        ))}
                    </div>
                )}
            </motion.div>
        </div>
    );
}

function WidgetAnimation({ active, interactive }: { active: boolean; interactive: boolean }) {
    const [selectedReply, setSelectedReply] = useState<string | null>(null);
    const [showWidget, setShowWidget] = useState(!interactive);

    useEffect(() => {
        if (!active) {
            setSelectedReply(null);
            setShowWidget(!interactive);
        }
    }, [active, interactive]);

    useEffect(() => {
        if (!active || interactive) return;
        const t = setTimeout(() => setShowWidget(true), 800);
        return () => clearTimeout(t);
    }, [active, interactive]);

    const handleDeploy = () => setShowWidget(true);

    const quickReplies = ['Cek Resi', 'Katalog'];

    return (
        <div className="relative h-full w-full">
            <motion.button
                type="button"
                disabled={!interactive || showWidget}
                onClick={handleDeploy}
                className={`absolute left-2 top-2 rounded-lg bg-surface-dark p-3 text-left font-mono text-[9px] leading-relaxed text-on-dark shadow-lg transition-all ${
                    interactive && !showWidget ? 'cursor-pointer ring-2 ring-primary/0 hover:ring-primary/40' : 'cursor-default'
                }`}
                initial={{ opacity: 0, y: 10 }}
                animate={active ? { opacity: 1, y: 0 } : {}}
                transition={{ duration: 0.5 }}
                whileTap={interactive && !showWidget ? { scale: 0.98 } : {}}
            >
                <div className="text-on-dark-soft">{'<!-- Embed Code -->'}</div>
                <div>
                    <span className="text-pink-400">{'<script'}</span>
                    <span className="text-blue-300">{' src='}</span>
                    <span className="text-yellow-300">{'"chatbot.js"'}</span>
                    <span className="text-pink-400">{'>'}</span>
                </div>
                <div>
                    <span className="text-pink-400">{'</script>'}</span>
                </div>
                {interactive && !showWidget && (
                    <div className="mt-2 text-center text-[8px] font-medium text-secondary">Klik untuk pasang widget</div>
                )}
            </motion.button>

            {(showWidget || !interactive) && (
                <motion.div
                    className="absolute bottom-2 right-2 flex w-40 flex-col overflow-hidden rounded-xl border border-hairline bg-zinc-900 shadow-xl sm:w-44"
                    initial={{ scale: 0.8, opacity: 0, y: 20 }}
                    animate={active ? { scale: 1, opacity: 1, y: 0 } : {}}
                    transition={{ delay: interactive ? 0 : 0.8, type: 'spring', stiffness: 300, damping: 25 }}
                >
                    <div className="bg-primary px-3 py-2 text-[10px] font-medium text-on-primary">CS Assistant</div>
                    <div className="p-3">
                        <motion.div
                            className="mb-2 w-fit rounded-lg rounded-tl-none bg-zinc-800 px-2 py-1.5 text-[9px] text-on-dark-soft"
                            initial={{ opacity: 0, x: -10 }}
                            animate={active ? { opacity: 1, x: 0 } : {}}
                            transition={{ delay: interactive ? 0.2 : 1.2 }}
                        >
                            {selectedReply
                                ? `Baik, saya bantu cek ${selectedReply.toLowerCase()}...`
                                : 'Halo! Ada yang bisa dibantu?'}
                        </motion.div>
                        {!selectedReply && (
                            <motion.div
                                className="flex flex-wrap gap-1"
                                initial={{ opacity: 0 }}
                                animate={active ? { opacity: 1 } : {}}
                                transition={{ delay: interactive ? 0.4 : 1.6 }}
                            >
                                {quickReplies.map((label) => (
                                    <motion.button
                                        key={label}
                                        type="button"
                                        whileTap={interactive ? { scale: 0.95 } : {}}
                                        onClick={() => interactive && setSelectedReply(label)}
                                        disabled={!interactive}
                                        className={`rounded-full border px-2 py-0.5 text-[8px] transition-colors ${
                                            interactive
                                                ? 'cursor-pointer border-primary/30 text-primary hover:bg-accent-muted'
                                                : 'border-hairline text-primary'
                                        }`}
                                    >
                                        {label}
                                    </motion.button>
                                ))}
                            </motion.div>
                        )}
                    </div>
                </motion.div>
            )}
        </div>
    );
}

function WhatsAppAnimation({ active, interactive }: { active: boolean; interactive: boolean }) {
    const [connected, setConnected] = useState(false);
    const [msgCount, setMsgCount] = useState(0);

    const messages = [
        { text: 'Halo kak!', time: '08:23' },
        { text: 'Pesanan #12345 sudah dikirim ya 🚚', time: '08:23' },
        { text: 'Estimasi sampai besok pagi', time: '08:24' },
    ];

    useEffect(() => {
        if (!active) {
            setConnected(false);
            setMsgCount(0);
        }
    }, [active]);

    useEffect(() => {
        if (!active || interactive) return;
        const t = setTimeout(() => setConnected(true), 800);
        return () => clearTimeout(t);
    }, [active, interactive]);

    useEffect(() => {
        if (!connected || interactive) return;
        const timers = messages.map((_, i) =>
            setTimeout(() => setMsgCount((c) => Math.max(c, i + 1)), 1000 + i * 600)
        );
        return () => timers.forEach(clearTimeout);
    }, [connected, interactive]);

    useEffect(() => {
        if (!connected || !interactive) return;
        const interval = setInterval(() => {
            setMsgCount((c) => (c >= messages.length ? c : c + 1));
        }, 700);
        return () => clearInterval(interval);
    }, [connected, interactive]);

    const handleScan = () => {
        if (!connected) setConnected(true);
    };

    const isConnected = interactive ? connected : active;
    const visibleCount = interactive ? (connected ? msgCount : 0) : msgCount;

    return (
        <div className="flex h-full w-full items-center justify-center gap-4 sm:gap-6">
            <motion.button
                type="button"
                disabled={!interactive || connected}
                onClick={handleScan}
                className={`relative flex h-20 w-20 items-center justify-center rounded-xl bg-zinc-900 p-2 shadow-sm transition-all ${
                    interactive && !connected
                        ? 'cursor-pointer ring-2 ring-transparent hover:ring-primary/30'
                        : 'cursor-default'
                }`}
                initial={{ opacity: 0, scale: 0.9 }}
                animate={active ? { opacity: 1, scale: 1 } : {}}
                transition={{ duration: 0.5 }}
                whileTap={interactive && !connected ? { scale: 0.95 } : {}}
            >
                <div className="grid grid-cols-4 gap-1">
                    {Array.from({ length: 16 }).map((_, i) => (
                        <span
                            key={i}
                            className="h-2.5 w-2.5 rounded-sm bg-ink"
                            style={{ opacity: ((i * 7 + 3) % 10) / 10 + 0.4 }}
                        />
                    ))}
                </div>
                {isConnected && (
                    <motion.div
                        className="absolute left-0 right-0 top-0 h-0.5 bg-primary shadow-[0_0_8px_rgba(79,70,229,0.8)]"
                        animate={{ y: [0, 80, 0] }}
                        transition={{ duration: 2, repeat: Infinity, ease: 'linear' }}
                    />
                )}
                {interactive && !connected && (
                    <span className="absolute -bottom-5 left-1/2 w-max -translate-x-1/2 text-[8px] font-medium text-primary">
                        Klik QR
                    </span>
                )}
            </motion.button>

            <div className="flex w-32 flex-col gap-2 sm:w-36">
                {!isConnected && interactive && (
                    <p className="text-center text-[9px] text-muted">Scan QR untuk connect WA</p>
                )}
                {messages.slice(0, visibleCount).map((msg, i) => (
                    <motion.div
                        key={i}
                        className="relative rounded-lg rounded-tl-none bg-zinc-900 px-2 py-1.5 shadow-sm"
                        initial={{ opacity: 0, x: -20 }}
                        animate={{ opacity: 1, x: 0 }}
                        transition={{ type: 'spring', stiffness: 400, damping: 30 }}
                    >
                        <div className="text-[9px] text-on-dark-soft">{msg.text}</div>
                        <div className="mt-0.5 text-right text-[7px] text-muted">{msg.time}</div>
                    </motion.div>
                ))}
            </div>
        </div>
    );
}

function HandoffFeatureAnimation({ active, interactive }: { active: boolean; interactive: boolean }) {
    const [phase, setPhase] = useState(0);
    const [isAutoPlaying, setIsAutoPlaying] = useState(true);

    useEffect(() => {
        if (!active) {
            setPhase(0);
            setIsAutoPlaying(true);
        }
    }, [active]);

    useEffect(() => {
        if (!active || !isAutoPlaying || interactive) return;
        const timeouts = [
            setTimeout(() => setPhase(1), 800),
            setTimeout(() => setPhase(2), 1600),
            setTimeout(() => setPhase(3), 2400),
            setTimeout(() => setPhase(4), 3600),
        ];
        return () => timeouts.forEach(clearTimeout);
    }, [active, isAutoPlaying, interactive]);

    useEffect(() => {
        if (!active || !isAutoPlaying || !interactive) return;
        const interval = setInterval(() => {
            setPhase((p) => (p >= 4 ? 4 : p + 1));
        }, 1500);
        return () => clearInterval(interval);
    }, [active, isAutoPlaying, interactive]);

    const handleAdvance = () => {
        setIsAutoPlaying(false);
        setPhase((p) => (p >= 4 ? 0 : p + 1));
        setTimeout(() => setIsAutoPlaying(true), 5000);
    };

    return (
        <div className="flex h-full w-full flex-col items-center justify-center gap-3">
            {interactive && (
                <div className="flex w-full max-w-[220px] items-center justify-between">
                    <div className="flex gap-1">
                        {[0, 1, 2, 3, 4].map((p) => (
                            <div
                                key={p}
                                className={`h-1.5 w-3 rounded-full transition-colors ${
                                    phase >= p ? 'bg-primary' : 'bg-hairline'
                                }`}
                            />
                        ))}
                    </div>
                    <motion.button
                        type="button"
                        whileTap={{ scale: 0.95 }}
                        onClick={handleAdvance}
                        className="rounded-md bg-primary px-2.5 py-1 text-[9px] font-medium text-on-primary"
                    >
                        {phase >= 4 ? '↺ Ulang' : 'Lanjut →'}
                    </motion.button>
                </div>
            )}

            <div className="flex w-full max-w-[220px] items-center justify-between rounded-lg border border-hairline bg-zinc-900 p-2 shadow-sm">
                <div className="flex items-center gap-2">
                    <div className="h-6 w-6 rounded-full bg-surface-strong" />
                    <div className="space-y-1">
                        <div className="h-2 w-16 rounded bg-surface-strong" />
                        <div className="h-1.5 w-12 rounded bg-zinc-800" />
                    </div>
                </div>
                <motion.div
                    className={`flex items-center gap-1 rounded-full px-2 py-0.5 text-[8px] font-medium ${
                        phase >= 2 ? 'bg-surface-strong text-on-dark' : 'bg-primary/10 text-primary'
                    }`}
                    animate={phase === 2 ? { scale: [1, 1.1, 1] } : {}}
                >
                    <span className={`h-1.5 w-1.5 rounded-full ${phase >= 2 ? 'bg-muted' : 'bg-current'}`} />
                    {phase >= 2 ? 'Agen: Budi' : 'AI Assistant'}
                </motion.div>
            </div>

            <div className="flex w-full max-w-[220px] flex-col gap-2">
                <motion.div
                    className="self-end rounded-lg rounded-tr-none bg-primary px-2 py-1.5 text-[9px] text-on-primary"
                    initial={{ opacity: 0, y: 5 }}
                    animate={phase >= 1 ? { opacity: 1, y: 0 } : {}}
                >
                    Tolong bicara dengan operator
                </motion.div>
                <motion.div
                    className={`self-start rounded-lg rounded-tl-none px-2 py-1.5 text-[9px] ${
                        phase >= 3 ? 'bg-accent-muted text-on-dark' : 'bg-zinc-800 text-on-dark-soft'
                    }`}
                    initial={{ opacity: 0, y: 5 }}
                    animate={phase >= 2 ? { opacity: 1, y: 0 } : {}}
                >
                    {phase >= 3 ? 'Halo, dengan Budi. Ada yang bisa dibantu?' : 'Meneruskan ke agen...'}
                </motion.div>
            </div>
        </div>
    );
}
