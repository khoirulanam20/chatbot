'use client';

import { cn } from '@/lib/utils';
import { motion } from 'framer-motion';
import React, { useState } from 'react';

export const WobbleCard = ({
    children,
    containerClassName,
    className,
}: {
    children: React.ReactNode;
    containerClassName?: string;
    className?: string;
}) => {
    const [mousePosition, setMousePosition] = useState({ x: 0, y: 0 });
    const [isHovering, setIsHovering] = useState(false);

    const handleMouseMove = (event: React.MouseEvent<HTMLDivElement>) => {
        const { clientX, clientY } = event;
        const rect = event.currentTarget.getBoundingClientRect();
        const width = rect.width;
        const height = rect.height;
        const x = (clientX - (rect.left + width / 2)) / 20;
        const y = (clientY - (rect.top + height / 2)) / 20;
        setMousePosition({ x, y });
    };

    return (
        <motion.section
            onMouseMove={handleMouseMove}
            onMouseEnter={() => setIsHovering(true)}
            onMouseLeave={() => {
                setIsHovering(false);
                setMousePosition({ x: 0, y: 0 });
            }}
            style={{
                transform: isHovering
                    ? `translate3d(${mousePosition.x}px, ${mousePosition.y}px, 0) scale3d(1, 1, 1)`
                    : 'translate3d(0px, 0px, 0) scale3d(1, 1, 1)',
                transition: 'transform 0.1s ease-out',
            }}
            className={cn('mx-auto w-full', containerClassName)}
        >
            <div
                className={cn(
                    'relative overflow-hidden rounded-2xl bg-surface-dark-elevated border border-white/[0.1]',
                    className,
                )}
            >
                <div className="relative h-full">{children}</div>
            </div>
        </motion.section>
    );
};
