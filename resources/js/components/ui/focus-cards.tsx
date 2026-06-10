'use client';

import { cn } from '@/lib/utils';
import React, { useState } from 'react';

export const Card = ({
    card,
    index,
    hovered,
    setHovered,
}: {
    card: { title: string; src: string };
    index: number;
    hovered: number | null;
    setHovered: React.Dispatch<React.SetStateAction<number | null>>;
}) => (
    <div
        onMouseEnter={() => setHovered(index)}
        onMouseLeave={() => setHovered(null)}
        className={cn(
            'relative h-60 w-full overflow-hidden rounded-lg bg-black transition-all duration-300 ease-out md:h-96',
            hovered !== null && hovered !== index && 'blur-sm scale-[0.98]',
        )}
    >
        <div className="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent z-10" />
        <div className="absolute inset-0 bg-surface-dark-elevated flex items-end p-6 z-20">
            <div className="text-xl font-medium text-on-dark md:text-2xl">{card.title}</div>
        </div>
        {card.src && (
            <div
                className="absolute inset-0 bg-cover bg-center opacity-40"
                style={{ backgroundImage: `url(${card.src})` }}
            />
        )}
    </div>
);

export function FocusCards({ cards }: { cards: { title: string; src: string }[] }) {
    const [hovered, setHovered] = useState<number | null>(null);

    return (
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 max-w-6xl mx-auto w-full">
            {cards.map((card, index) => (
                <Card
                    key={card.title}
                    card={card}
                    index={index}
                    hovered={hovered}
                    setHovered={setHovered}
                />
            ))}
        </div>
    );
}
