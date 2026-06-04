import { Link } from '@inertiajs/react';

interface Props {
    chatbotId: number;
    active: 'edit' | 'persona' | 'embed';
}

const tabs = [
    { key: 'edit' as const, label: 'Konfigurasi', href: (id: number) => `/admin/chatbot/${id}/edit` },
    { key: 'persona' as const, label: 'Persona', href: (id: number) => `/admin/chatbot/${id}/persona` },
    { key: 'embed' as const, label: 'Embed', href: (id: number) => `/admin/chatbot/${id}/embed-code` },
];

export function ChatbotSubNav({ chatbotId, active }: Props) {
    return (
        <nav className="flex gap-1 rounded-lg border border-hairline bg-surface-soft p-1">
            {tabs.map((tab) => (
                <Link
                    key={tab.key}
                    href={tab.href(chatbotId)}
                    className={`rounded-md px-4 py-2 text-sm font-medium transition-colors ${
                        active === tab.key
                            ? 'bg-surface-card text-ink shadow-sm'
                            : 'text-muted hover:text-ink'
                    }`}
                >
                    {tab.label}
                </Link>
            ))}
        </nav>
    );
}
