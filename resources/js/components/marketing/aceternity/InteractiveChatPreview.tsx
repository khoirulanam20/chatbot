import { HeroIllustration } from '../illustrations/HeroIllustration';

export function InteractiveChatPreview() {
    return (
        <div className="relative">
            <div className="absolute -inset-1 rounded-2xl bg-gradient-to-r from-primary/20 via-brand-accent/20 to-primary/20 blur-xl" />
            <div className="relative rounded-2xl border border-white/[0.1] bg-black/40 p-2 backdrop-blur-sm">
                <HeroIllustration />
            </div>
        </div>
    );
}
