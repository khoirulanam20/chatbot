import { motion, useAnimate } from 'framer-motion';
import { Send } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { useReducedMotion } from '../motion/useReducedMotion';
import { float } from '../motion/variants';

interface Message {
    id: string;
    side: 'user' | 'bot';
    text: string;
}

const defaultMessages: Message[] = [
    { id: '1', side: 'bot', text: 'Halo! Ada yang bisa dibantu?' },
];

export function HeroIllustration() {
    const reducedMotion = useReducedMotion();
    const [messages, setMessages] = useState<Message[]>(defaultMessages);
    const [inputValue, setInputValue] = useState('');
    const [isTyping, setIsTyping] = useState(false);
    const messagesEndRef = useRef<HTMLDivElement>(null);
    const [scope, animate] = useAnimate();

    const scrollToBottom = () => {
        if (scope.current) {
            scope.current.scrollTo({
                top: scope.current.scrollHeight,
                behavior: 'smooth'
            });
        }
    };

    useEffect(() => {
        scrollToBottom();
    }, [messages, isTyping]);

    const handleSend = async () => {
        if (!inputValue.trim() || isTyping) return;

        const userMsg = inputValue.trim();
        setInputValue('');
        
        // Add user message
        setMessages((prev) => [...prev, { id: Date.now().toString(), side: 'user', text: userMsg }]);
        
        // Show typing indicator
        setIsTyping(true);
        
        if (!reducedMotion) {
            await new Promise((r) => setTimeout(r, 800));
        }

        // Add bot response
        setIsTyping(false);
        setMessages((prev) => [
            ...prev,
            {
                id: Date.now().toString(),
                side: 'bot',
                text: 'Terima kasih atas pertanyaannya. Tim kami akan segera merespons.',
            },
        ]);
    };

    const handleKeyDown = (e: React.KeyboardEvent) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            handleSend();
        }
    };

    // Initial animation sequence
    useEffect(() => {
        if (reducedMotion || messages.length > 1) return;

        let isMounted = true;

        const runSequence = async () => {
            await new Promise((r) => setTimeout(r, 1000));
            if (!isMounted) return;

            setMessages((prev) => [...prev, { id: 'auto-user', side: 'user', text: 'Apa syarat pengembalian?' }]);
            setIsTyping(true);

            await new Promise((r) => setTimeout(r, 1200));
            if (!isMounted) return;

            setIsTyping(false);
            setMessages((prev) => [
                ...prev,
                { id: 'auto-bot', side: 'bot', text: 'Syarat pengembalian 7 hari dengan struk asli.' },
            ]);
        };

        runSequence();

        return () => {
            isMounted = false;
        };
    }, [reducedMotion]);

    return (
        <motion.div
            className="relative mx-auto w-full max-w-lg"
            {...(!reducedMotion ? float : {})}
        >
            <div className="absolute -inset-4 -z-10 bg-primary/10 opacity-20 blur-3xl" />
            <div className="overflow-hidden rounded-xl border border-hairline bg-surface-soft shadow-2xl">
                {/* Browser Header */}
                <div className="flex items-center gap-2 border-b border-hairline bg-canvas px-4 py-3">
                    <div className="flex gap-1.5">
                        <span className="h-2.5 w-2.5 rounded-full bg-error/60" />
                        <span className="h-2.5 w-2.5 rounded-full bg-warning/60" />
                        <span className="h-2.5 w-2.5 rounded-full bg-success/60" />
                    </div>
                    <div className="ml-2 flex flex-1 items-center justify-center rounded-md bg-surface-card px-3 py-1 text-xs text-muted">
                        <span className="opacity-50">🔒</span>
                        <span className="ml-1">toko-online-anda.com</span>
                    </div>
                </div>

                {/* Browser Content */}
                <div className="relative h-[400px] bg-canvas p-6">
                    {/* Dummy Website Content */}
                    <div className="space-y-6 opacity-30">
                        <div className="flex items-center justify-between">
                            <div className="h-6 w-24 rounded bg-surface-strong" />
                            <div className="flex gap-4">
                                <div className="h-4 w-12 rounded bg-surface-strong" />
                                <div className="h-4 w-12 rounded bg-surface-strong" />
                                <div className="h-4 w-12 rounded bg-surface-strong" />
                            </div>
                        </div>
                        <div className="space-y-3">
                            <div className="h-8 w-3/4 rounded bg-surface-strong" />
                            <div className="h-4 w-full rounded bg-surface-strong" />
                            <div className="h-4 w-5/6 rounded bg-surface-strong" />
                        </div>
                        <div className="grid grid-cols-3 gap-4">
                            {[1, 2, 3].map((i) => (
                                <div key={i} className="h-24 rounded bg-surface-strong" />
                            ))}
                        </div>
                    </div>

                    {/* Chat Widget */}
                    <div className="absolute bottom-4 right-4 flex w-72 flex-col overflow-hidden rounded-xl border border-hairline bg-canvas shadow-xl">
                        {/* Widget Header */}
                        <div className="flex items-center justify-between bg-primary px-4 py-3">
                            <div className="flex items-center gap-2">
                                <div className="flex h-6 w-6 items-center justify-center rounded-full bg-canvas/20 text-xs text-on-primary">
                                    🤖
                                </div>
                                <span className="text-sm font-medium text-on-primary">CS Assistant</span>
                            </div>
                            <motion.span
                                className="flex items-center gap-1.5 text-[10px] font-medium text-on-primary/90"
                                animate={reducedMotion ? {} : { opacity: [0.5, 1, 0.5] }}
                                transition={{ duration: 2, repeat: Infinity }}
                            >
                                <span className="h-1.5 w-1.5 rounded-full bg-success" />
                                Online
                            </motion.span>
                        </div>

                        {/* Chat Messages */}
                        <div className="flex h-48 flex-col gap-3 overflow-y-auto p-4" ref={scope}>
                            {messages.map((msg) => (
                                <ChatBubble key={msg.id} side={msg.side} text={msg.text} reduced={reducedMotion} />
                            ))}
                            {isTyping && (
                                <motion.div
                                    initial={{ opacity: 0, y: 5 }}
                                    animate={{ opacity: 1, y: 0 }}
                                    className="flex max-w-[80%] items-center gap-1 rounded-lg rounded-tl-none bg-surface-card px-3 py-2"
                                >
                                    {[0, 1, 2].map((i) => (
                                        <motion.span
                                            key={i}
                                            className="h-1.5 w-1.5 rounded-full bg-muted"
                                            animate={reducedMotion ? {} : { y: [0, -3, 0] }}
                                            transition={{ duration: 0.6, repeat: Infinity, delay: i * 0.2 }}
                                        />
                                    ))}
                                </motion.div>
                            )}
                            <div ref={messagesEndRef} />
                        </div>

                        {/* Chat Input */}
                        <div className="border-t border-hairline bg-canvas p-3">
                            <span className="absolute -top-6 right-4 rounded-full bg-primary px-2 py-0.5 text-[9px] font-medium text-white shadow-sm">
                                Coba Kirim!
                            </span>
                            <div className="flex items-center gap-2 rounded-lg border border-hairline bg-surface-soft px-3 py-2 focus-within:border-primary focus-within:ring-1 focus-within:ring-primary/20">
                                <input
                                    type="text"
                                    value={inputValue}
                                    onChange={(e) => setInputValue(e.target.value)}
                                    onKeyDown={handleKeyDown}
                                    placeholder="Ketik pertanyaan..."
                                    className="w-full bg-transparent text-xs outline-none placeholder:text-muted"
                                />
                                <motion.button
                                    whileTap={{ scale: 0.9 }}
                                    onClick={handleSend}
                                    disabled={!inputValue.trim() || isTyping}
                                    className="flex h-6 w-6 items-center justify-center rounded-md bg-primary text-on-primary disabled:opacity-50"
                                >
                                    <Send className="h-3 w-3" />
                                </motion.button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </motion.div>
    );
}

function ChatBubble({ side, text, reduced }: { side: 'user' | 'bot'; text: string; reduced: boolean }) {
    const isUser = side === 'user';

    return (
        <motion.div
            initial={reduced ? {} : { opacity: 0, y: 10, scale: 0.95 }}
            animate={reduced ? {} : { opacity: 1, y: 0, scale: 1 }}
            className={`flex ${isUser ? 'justify-end' : 'justify-start'}`}
        >
            <div
                className={`max-w-[85%] rounded-lg px-3 py-2 text-xs leading-relaxed shadow-sm ${
                    isUser
                        ? 'rounded-tr-none bg-primary text-on-primary'
                        : 'rounded-tl-none bg-surface-card text-body'
                }`}
            >
                {text}
            </div>
        </motion.div>
    );
}
