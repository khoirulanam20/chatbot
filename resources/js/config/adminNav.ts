import {
    Bot,
    Building2,
    LayoutDashboard,
    MessageSquare,
    BookOpen,
    Settings,
    Smartphone,
    Users,
    Palette,
    type LucideIcon,
} from 'lucide-react';

export interface AdminNavItem {
    name: string;
    href: string;
    icon: LucideIcon;
    roles: string[];
    primary?: boolean;
    shortLabel?: string;
}

export const adminNavItems: AdminNavItem[] = [
    { name: 'Dashboard', href: '/admin/dashboard', icon: LayoutDashboard, roles: ['super_admin', 'admin', 'operator', 'viewer'], primary: true, shortLabel: 'Home' },
    { name: 'Percakapan', href: '/admin/conversations', icon: MessageSquare, roles: ['super_admin', 'admin', 'operator', 'viewer'], primary: true, shortLabel: 'Chat' },
    { name: 'Chatbot', href: '/admin/chatbot', icon: Bot, roles: ['super_admin', 'admin', 'operator', 'viewer'], primary: true },
    { name: 'Knowledge Base', href: '/admin/knowledge', icon: BookOpen, roles: ['super_admin', 'admin', 'operator', 'viewer'], primary: true, shortLabel: 'KB' },
    { name: 'WhatsApp', href: '/admin/wa', icon: Smartphone, roles: ['super_admin', 'admin', 'operator', 'viewer'] },
    { name: 'Pengguna', href: '/admin/users', icon: Users, roles: ['super_admin', 'admin'] },
    { name: 'Tenants', href: '/admin/tenants', icon: Building2, roles: ['super_admin'] },
    { name: 'CMS Landing', href: '/admin/marketing', icon: Palette, roles: ['super_admin'] },
    { name: 'Pengaturan AI', href: '/admin/settings', icon: Settings, roles: ['super_admin', 'admin', 'operator', 'viewer'] },
];

export function getVisibleNav(role: string | undefined): AdminNavItem[] {
    if (!role) return [];
    return adminNavItems.filter((item) => item.roles.includes(role));
}

export function getPrimaryNav(role: string | undefined): AdminNavItem[] {
    return getVisibleNav(role).filter((item) => item.primary);
}

export function getSecondaryNav(role: string | undefined): AdminNavItem[] {
    return getVisibleNav(role).filter((item) => !item.primary);
}
