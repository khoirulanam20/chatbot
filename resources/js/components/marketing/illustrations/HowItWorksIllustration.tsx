import { motion, useInView, AnimatePresence } from 'framer-motion';
import { Upload, Settings, Zap, FileText, Smartphone, MessageSquare, User, BarChart3, Globe } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { useReducedMotion } from '../motion/useReducedMotion';

export interface HowItWorksIllustrationProps {
    onStepChange?: (step: number) => void;
}

export function HowItWorksIllustration({ onStepChange }: HowItWorksIllustrationProps) {
    const ref = useRef(null);
    const inView = useInView(ref, { once: true, margin: '-60px' });
    const reduced = useReducedMotion();
    const [activeStep, setActiveStep] = useState(0);

    useEffect(() => {
        if (!inView || reduced) return;

        const interval = setInterval(() => {
            setActiveStep((prev) => {
                const next = (prev + 1) % 3;
                onStepChange?.(next);
                return next;
            });
        }, 4000);

        return () => clearInterval(interval);
    }, [inView, reduced, onStepChange]);

    useEffect(() => {
        if (inView) onStepChange?.(0);
    }, [inView, onStepChange]);

    const handleStepClick = (index: number) => {
        setActiveStep(index);
        onStepChange?.(index);
    };

    const steps = [
        { label: 'Upload', icon: Upload },
        { label: 'Atur', icon: Settings },
        { label: 'Aktif', icon: Zap },
    ];

    return (
        <div ref={ref} aria-hidden="true" className="mb-10 flex flex-col gap-8 rounded-xl border border-hairline bg-zinc-800 p-6 sm:p-8">
            {/* Stepper Header */}
            <div className="relative mx-auto flex w-full max-w-2xl items-center justify-between">
                <svg className="absolute left-[10%] right-[10%] top-1/2 h-1 w-[80%] -translate-y-1/2" preserveAspectRatio="none">
                    <motion.path
                        d="M 0 2 L 1000 2"
                        stroke="var(--color-hairline, #E7E5E4)"
                        strokeWidth="4"
                        fill="none"
                        initial={{ pathLength: 0 }}
                        animate={{ pathLength: inView && !reduced ? 1 : 0 }}
                        transition={{ duration: 1.5, ease: "easeInOut" }}
                    />
                    <motion.path
                        d="M 0 2 L 1000 2"
                        stroke="var(--color-primary, #0066FF)"
                        strokeWidth="4"
                        fill="none"
                        initial={{ pathLength: 0 }}
                        animate={{ pathLength: inView && !reduced ? (activeStep) / 2 : 0 }}
                        transition={{ duration: 0.5, ease: "easeInOut" }}
                    />
                </svg>
                {steps.map((step, i) => {
                    const Icon = step.icon;
                    const isActive = activeStep === i;
                    const isPast = activeStep > i;
                    
                    return (
                        <div key={step.label} className="relative z-10 flex flex-col items-center gap-3">
                            <motion.button
                                whileTap={{ scale: 0.9 }}
                                onClick={() => handleStepClick(i)}
                                className={`flex h-14 w-14 sm:h-16 sm:w-16 items-center justify-center rounded-xl border-2 transition-all cursor-pointer ${
                                    isActive 
                                        ? 'border-primary bg-primary text-on-primary shadow-lg ring-4 ring-primary/20' 
                                        : isPast
                                            ? 'border-primary bg-zinc-900 text-primary'
                                            : 'border-hairline bg-zinc-900 text-muted hover:border-primary/50'
                                }`}
                                animate={isActive && !reduced ? { scale: [1, 1.05, 1] } : {}}
                                transition={{ duration: 2, repeat: isActive ? Infinity : 0 }}
                            >
                                <Icon className="h-5 w-5 sm:h-6 sm:w-6" />
                            </motion.button>
                            <span className={`text-xs sm:text-sm font-semibold ${isActive ? 'text-on-dark' : 'text-muted'}`}>
                                {step.label}
                            </span>
                        </div>
                    );
                })}
            </div>

            {/* Preview Panel */}
            <div className="relative mx-auto w-full max-w-2xl overflow-hidden rounded-xl border border-hairline bg-zinc-900 shadow-sm min-h-[240px] sm:min-h-[280px]">
                <AnimatePresence mode="wait">
                    {activeStep === 0 && (
                        <motion.div
                            key="step-0"
                            initial={{ opacity: 0, y: 10 }}
                            animate={{ opacity: 1, y: 0 }}
                            exit={{ opacity: 0, y: -10 }}
                            transition={{ duration: 0.3 }}
                            className="absolute inset-0 flex flex-col items-center justify-center p-6"
                        >
                            <div className="flex w-full max-w-sm flex-col gap-4">
                                <div className="flex items-center gap-3 rounded-lg border border-hairline bg-zinc-800 p-3">
                                    <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded bg-primary/10 text-primary">
                                        <FileText className="h-5 w-5" />
                                    </div>
                                    <div className="flex-1 space-y-1.5">
                                        <div className="h-2 w-24 rounded bg-surface-strong" />
                                        <div className="h-1.5 w-16 rounded bg-surface-strong/60" />
                                    </div>
                                    <motion.div 
                                        className="h-4 w-4 rounded-full border-2 border-primary border-t-transparent"
                                        animate={{ rotate: 360 }}
                                        transition={{ duration: 1, repeat: Infinity, ease: "linear" }}
                                    />
                                </div>
                                <div className="flex items-center gap-3 rounded-lg border border-hairline bg-zinc-800 p-3 opacity-50">
                                    <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded bg-primary/10 text-primary">
                                        <Globe className="h-5 w-5" />
                                    </div>
                                    <div className="flex-1 space-y-1.5">
                                        <div className="h-2 w-32 rounded bg-surface-strong" />
                                        <div className="h-1.5 w-20 rounded bg-surface-strong/60" />
                                    </div>
                                </div>
                            </div>
                        </motion.div>
                    )}

                    {activeStep === 1 && (
                        <motion.div
                            key="step-1"
                            initial={{ opacity: 0, y: 10 }}
                            animate={{ opacity: 1, y: 0 }}
                            exit={{ opacity: 0, y: -10 }}
                            transition={{ duration: 0.3 }}
                            className="absolute inset-0 flex items-center justify-center gap-6 p-6"
                        >
                            <div className="flex flex-col items-center gap-3">
                                <div className="flex h-24 w-24 items-center justify-center rounded-xl border border-hairline bg-zinc-800 shadow-sm">
                                    <div className="grid grid-cols-4 gap-1 p-2">
                                        {Array.from({ length: 16 }).map((_, i) => (
                                            <span key={i} className="h-3 w-3 rounded-sm bg-ink" style={{ opacity: Math.random() * 0.5 + 0.5 }} />
                                        ))}
                                    </div>
                                </div>
                                <span className="text-[10px] font-medium text-muted">Scan QR WA</span>
                            </div>
                            
                            <div className="h-20 w-px bg-hairline" />
                            
                            <div className="flex flex-col items-center gap-3">
                                <div className="flex h-24 w-32 flex-col justify-center rounded-xl border border-hairline bg-surface-dark p-3 shadow-sm">
                                    <div className="space-y-1.5 font-mono text-[8px] text-on-dark-soft">
                                        <div className="text-pink-400">{'<script'}</div>
                                        <div className="pl-2 text-yellow-300">src="bot.js"</div>
                                        <div className="text-pink-400">{'></script>'}</div>
                                    </div>
                                </div>
                                <span className="text-[10px] font-medium text-muted">Embed Widget</span>
                            </div>
                        </motion.div>
                    )}

                    {activeStep === 2 && (
                        <motion.div
                            key="step-2"
                            initial={{ opacity: 0, y: 10 }}
                            animate={{ opacity: 1, y: 0 }}
                            exit={{ opacity: 0, y: -10 }}
                            transition={{ duration: 0.3 }}
                            className="absolute inset-0 flex flex-col p-6"
                        >
                            <div className="mb-4 flex items-center justify-between border-b border-hairline pb-4">
                                <div className="flex items-center gap-2">
                                    <div className="flex h-8 w-8 items-center justify-center rounded bg-primary text-on-primary">
                                        <MessageSquare className="h-4 w-4" />
                                    </div>
                                    <div>
                                        <div className="text-xs font-bold text-on-dark">Inbox Terpusat</div>
                                        <div className="text-[10px] text-muted">2 percakapan aktif</div>
                                    </div>
                                </div>
                                <motion.div
                                    className="flex items-center gap-1.5 rounded-full bg-success/10 px-2 py-1"
                                    animate={{ opacity: [0.5, 1, 0.5] }}
                                    transition={{ duration: 1.5, repeat: Infinity }}
                                >
                                    <span className="h-1.5 w-1.5 rounded-full bg-success" />
                                    <span className="text-[9px] font-medium text-success">Sistem Aktif</span>
                                </motion.div>
                            </div>
                            
                            <div className="flex flex-1 gap-4">
                                <div className="w-1/3 space-y-2 border-r border-hairline pr-4">
                                    <div className="rounded border border-primary bg-primary/5 p-2">
                                        <div className="flex items-center justify-between">
                                            <span className="text-[10px] font-semibold text-on-dark">Budi S.</span>
                                            <Smartphone className="h-3 w-3 text-muted" />
                                        </div>
                                        <div className="mt-1 truncate text-[9px] text-muted">Tanya stok barang...</div>
                                    </div>
                                    <div className="rounded border border-hairline bg-zinc-800 p-2 opacity-50">
                                        <div className="flex items-center justify-between">
                                            <span className="text-[10px] font-semibold text-on-dark">Guest_123</span>
                                            <Globe className="h-3 w-3 text-muted" />
                                        </div>
                                        <div className="mt-1 truncate text-[9px] text-muted">Cara retur gmn?</div>
                                    </div>
                                </div>
                                <div className="flex flex-1 flex-col gap-2">
                                    <div className="w-fit max-w-[80%] rounded-lg rounded-tl-none bg-zinc-800 p-2 text-[10px] text-on-dark-soft">
                                        Halo, mau tanya stok barang A ukuran L masih ada?
                                    </div>
                                    <motion.div 
                                        className="w-fit max-w-[80%] self-end rounded-lg rounded-tr-none bg-primary p-2 text-[10px] text-on-primary"
                                        initial={{ opacity: 0, scale: 0.9, originX: 1, originY: 0 }}
                                        animate={{ opacity: 1, scale: 1 }}
                                        transition={{ delay: 0.5 }}
                                    >
                                        Halo Budi! Stok barang A ukuran L saat ini masih tersedia 5 pcs. Ingin langsung dipesankan?
                                    </motion.div>
                                </div>
                            </div>
                        </motion.div>
                    )}
                </AnimatePresence>
            </div>
        </div>
    );
}
