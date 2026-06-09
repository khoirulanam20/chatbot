import { motion, useInView } from 'framer-motion';
import { RotateCcw } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { useReducedMotion } from '../motion/useReducedMotion';

export function HandoffIllustration() {
    const ref = useRef(null);
    const inView = useInView(ref, { once: false, margin: '-40px' });
    const reduced = useReducedMotion();
    const [phase, setPhase] = useState(0);
    const [isAutoPlaying, setIsAutoPlaying] = useState(true);

    useEffect(() => {
        if (!inView || reduced || !isAutoPlaying) return;

        const interval = setInterval(() => {
            setPhase((p) => (p >= 5 ? 5 : p + 1));
        }, 1500);

        return () => clearInterval(interval);
    }, [inView, reduced, isAutoPlaying]);

    const handleAdvance = () => {
        setIsAutoPlaying(false);
        if (phase >= 5) {
            setPhase(0);
        } else {
            setPhase((p) => p + 1);
        }
        
        // Resume autoplay after 5 seconds of inactivity
        setTimeout(() => setIsAutoPlaying(true), 5000);
    };

    return (
        <div ref={ref} aria-hidden="true" className="relative flex h-full min-h-[360px] w-full flex-col overflow-hidden bg-surface-soft p-4 sm:p-6">
            {/* Header controls */}
            <div className="mb-4 flex items-center justify-between">
                <div className="flex gap-1">
                    {[0, 1, 2, 3, 4, 5].map((p) => (
                        <div
                            key={p}
                            className={`h-1.5 w-4 rounded-full transition-colors ${
                                phase >= p ? 'bg-primary' : 'bg-hairline'
                            }`}
                        />
                    ))}
                </div>
                <motion.button
                    whileTap={{ scale: 0.95 }}
                    onClick={handleAdvance}
                    className="flex items-center gap-1.5 rounded-full bg-canvas px-3 py-1 text-xs font-medium text-ink shadow-sm hover:bg-surface-card"
                >
                    {phase >= 5 ? (
                        <>
                            <RotateCcw className="h-3 w-3" /> Ulangi
                        </>
                    ) : (
                        'Lanjut →'
                    )}
                </motion.button>
            </div>

            {/* Inbox Mockup */}
            <div className="flex flex-1 overflow-hidden rounded-xl border border-hairline bg-canvas shadow-lg">
                {/* Sidebar */}
                <div className="hidden w-1/3 flex-col border-r border-hairline sm:flex">
                    <div className="border-b border-hairline p-3">
                        <div className="h-6 w-full rounded bg-surface-soft" />
                    </div>
                    <div className="flex-1 overflow-hidden">
                        <div className="bg-surface-soft p-3">
                            <div className="flex items-center justify-between">
                                <span className="text-xs font-semibold text-ink">Budi Santoso</span>
                                <span className="text-[10px] text-muted">Baru saja</span>
                            </div>
                            <div className="mt-1 text-[10px] text-muted truncate">
                                {phase < 2 ? 'Tolong bicara dengan operator' : 'Halo, dengan CS Firstudio...'}
                            </div>
                        </div>
                        {[1, 2, 3].map((i) => (
                            <div key={i} className="border-t border-hairline p-3 opacity-50">
                                <div className="h-3 w-20 rounded bg-surface-strong mb-2" />
                                <div className="h-2 w-full rounded bg-surface-soft" />
                            </div>
                        ))}
                    </div>
                </div>

                {/* Main Chat Area */}
                <div className="flex flex-1 flex-col">
                    {/* Chat Header */}
                    <div className="flex items-center justify-between border-b border-hairline px-4 py-3">
                        <div className="flex items-center gap-3">
                            <div className="flex h-8 w-8 items-center justify-center rounded-full bg-surface-strong text-sm">
                                BS
                            </div>
                            <div>
                                <div className="text-sm font-semibold text-ink">Budi Santoso</div>
                                <div className="text-[10px] text-muted">WhatsApp</div>
                            </div>
                        </div>
                    <motion.div
                        className={`flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[10px] font-medium ${
                            phase >= 2 && phase < 5
                                ? 'bg-surface-strong text-ink'
                                : 'bg-primary/10 text-primary'
                        }`}
                        animate={phase === 2 || phase === 5 ? { scale: [1, 1.1, 1] } : {}}
                        transition={{ duration: 0.5 }}
                    >
                        <span className={`h-1.5 w-1.5 rounded-full ${phase >= 2 && phase < 5 ? 'bg-muted' : 'bg-current'}`} />
                        {phase >= 2 && phase < 5 ? 'Agen Aktif' : 'AI Aktif'}
                    </motion.div>
                    </div>

                    {/* Chat Messages */}
                    <div className="flex-1 space-y-4 overflow-y-auto p-4">
                        <div className="flex justify-end">
                            <div className="max-w-[80%] rounded-lg rounded-tr-none bg-surface-soft px-3 py-2 text-xs text-body">
                                Tolong bicara dengan operator
                            </div>
                        </div>
                        
                        {phase >= 1 && (
                            <motion.div
                                initial={{ opacity: 0, y: 10 }}
                                animate={{ opacity: 1, y: 0 }}
                                className="flex justify-center"
                            >
                                <div className="rounded-full bg-surface-strong px-3 py-1 text-[10px] text-ink">
                                    User meminta bantuan agen
                                </div>
                            </motion.div>
                        )}

                        {phase >= 3 && (
                            <motion.div
                                initial={{ opacity: 0, y: 10 }}
                                animate={{ opacity: 1, y: 0 }}
                                className="flex items-start gap-2"
                            >
                                <div className="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-surface-strong text-[10px] font-bold text-ink">
                                    CS
                                </div>
                                <div className="max-w-[80%] rounded-lg rounded-tl-none bg-surface-card px-3 py-2 text-xs text-body">
                                    Halo Budi, dengan CS Firstudio. Ada yang bisa dibantu terkait kendala aplikasinya?
                                </div>
                            </motion.div>
                        )}

                        {phase >= 4 && (
                            <motion.div
                                initial={{ opacity: 0, y: 10 }}
                                animate={{ opacity: 1, y: 0 }}
                                className="flex justify-end"
                            >
                                <div className="max-w-[80%] rounded-lg rounded-tr-none bg-surface-soft px-3 py-2 text-xs text-body">
                                    Sudah aman kak, terima kasih.
                                </div>
                            </motion.div>
                        )}

                        {phase >= 5 && (
                            <motion.div
                                initial={{ opacity: 0, y: 10 }}
                                animate={{ opacity: 1, y: 0 }}
                                className="flex justify-center"
                            >
                                <div className="rounded-full bg-primary/10 px-3 py-1 text-[10px] text-primary">
                                    Agen mengaktifkan kembali AI
                                </div>
                            </motion.div>
                        )}
                    </div>

                    {/* Chat Input */}
                    <div className="border-t border-hairline p-3">
                        {phase >= 2 && phase < 5 ? (
                            <motion.div
                                initial={{ opacity: 0, y: 10 }}
                                animate={{ opacity: 1, y: 0 }}
                                className="flex items-center gap-2 rounded-lg border border-hairline bg-canvas px-3 py-2"
                            >
                                <div className="h-4 flex-1 rounded bg-surface-soft" />
                                <div className="h-6 w-16 rounded bg-primary" />
                            </motion.div>
                        ) : (
                            <div className="flex items-center justify-center rounded-lg bg-surface-soft py-2 text-xs text-muted">
                                AI sedang menangani percakapan ini
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
}
