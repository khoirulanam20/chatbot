import { Menu } from 'lucide-react';
import { NotificationBell } from '@/components/NotificationBell';
import { Button } from '@/components/ui/button';

interface Props {
    onMenuOpen: () => void;
}

export function AdminMobileHeader({ onMenuOpen }: Props) {
    return (
        <header className="sticky top-0 z-20 flex items-center justify-between border-b border-hairline bg-canvas/95 px-4 py-3 backdrop-blur lg:hidden">
            <Button variant="outline" size="icon" onClick={onMenuOpen} aria-label="Buka menu">
                <Menu className="h-5 w-5" />
            </Button>
            <h1 className="font-display text-sm font-semibold tracking-tight">AI CS Chatbot</h1>
            <NotificationBell />
        </header>
    );
}
