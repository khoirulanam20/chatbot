import { ReactNode } from 'react';

interface StatsCardProps {
    title: string;
    value: string | number;
    icon?: ReactNode;
    subtitle?: string;
}

export function StatsCard({ title, value, icon, subtitle }: StatsCardProps) {
    return (
        <div className="rounded-lg border border-hairline bg-surface-card p-6">
            <div className="flex items-center justify-between">
                <div>
                    <p className="text-sm text-muted">{title}</p>
                    <p className="mt-1 font-display text-2xl font-semibold tracking-tight text-ink">
                        {value}
                    </p>
                    {subtitle && <p className="mt-1 text-xs text-muted">{subtitle}</p>}
                </div>
                {icon && <div className="text-muted">{icon}</div>}
            </div>
        </div>
    );
}
