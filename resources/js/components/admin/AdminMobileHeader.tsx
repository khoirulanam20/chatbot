import { NotificationBell } from '@/components/NotificationBell';

export function AdminMobileHeader() {
    return (
        <header className="sticky top-0 z-20 flex items-center justify-between border-b border-hairline bg-canvas/95 px-4 py-3 backdrop-blur lg:hidden">
            <h1 className="font-display text-sm font-semibold tracking-tight">AI CS Chatbot</h1>
            <NotificationBell />
        </header>
    );
}
