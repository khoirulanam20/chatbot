'use client';

import { cn } from '@/lib/utils';
import React, { useState } from 'react';

interface CompareProps {
    firstContent?: React.ReactNode;
    secondContent?: React.ReactNode;
    className?: string;
    firstContentClassName?: string;
    secondContentClassname?: string;
    slideMode?: 'hover' | 'drag';
    showHandlebar?: boolean;
    autoplay?: boolean;
    autoplayDuration?: number;
}

export const Compare = ({
    firstContent,
    secondContent,
    className,
    firstContentClassName,
    secondContentClassname,
    slideMode = 'hover',
    showHandlebar = true,
}: CompareProps) => {
    const [sliderPosition, setSliderPosition] = useState(50);
    const [isDragging, setIsDragging] = useState(false);

    const handleMove = (clientX: number, rect: DOMRect) => {
        const x = Math.max(0, Math.min(clientX - rect.left, rect.width));
        const percent = Math.max(0, Math.min((x / rect.width) * 100, 100));
        setSliderPosition(percent);
    };

    const handleMouseMove = (e: React.MouseEvent<HTMLDivElement>) => {
        if (!isDragging && slideMode === 'drag') return;
        if (slideMode === 'hover' || isDragging) {
            const rect = e.currentTarget.getBoundingClientRect();
            handleMove(e.clientX, rect);
        }
    };

    const handleMouseDown = () => {
        if (slideMode === 'drag') setIsDragging(true);
    };

    const handleMouseUp = () => {
        setIsDragging(false);
    };

    const handleTouchMove = (e: React.TouchEvent<HTMLDivElement>) => {
        if (!isDragging && slideMode === 'drag') return;
        const rect = e.currentTarget.getBoundingClientRect();
        handleMove(e.touches[0].clientX, rect);
    };

    return (
        <div
            className={cn('relative w-full overflow-hidden select-none', className)}
            onMouseMove={handleMouseMove}
            onMouseDown={handleMouseDown}
            onMouseUp={handleMouseUp}
            onMouseLeave={handleMouseUp}
            onTouchStart={handleMouseDown}
            onTouchEnd={handleMouseUp}
            onTouchMove={handleTouchMove}
        >
            <div
                className={cn(
                    'absolute inset-0 z-20 overflow-hidden',
                    firstContentClassName,
                )}
                style={{ clipPath: `inset(0 ${100 - sliderPosition}% 0 0)` }}
            >
                {firstContent}
            </div>
            <div className={cn('relative z-10', secondContentClassname)}>
                {secondContent}
            </div>
            {showHandlebar && (
                <div
                    className="absolute top-0 bottom-0 z-30 w-1 bg-white cursor-ew-resize"
                    style={{ left: `calc(${sliderPosition}% - 1px)` }}
                >
                    <div className="absolute top-1/2 left-1/2 h-8 w-8 -translate-x-1/2 -translate-y-1/2 rounded-full bg-white shadow-lg flex items-center justify-center">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                            <path d="M6 4L2 8L6 12" stroke="#333" strokeWidth="1.5" strokeLinecap="round" />
                            <path d="M10 4L14 8L10 12" stroke="#333" strokeWidth="1.5" strokeLinecap="round" />
                        </svg>
                    </div>
                </div>
            )}
        </div>
    );
};
