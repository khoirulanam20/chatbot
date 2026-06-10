'use client';

import { cn } from '@/lib/utils';
import { motion, AnimatePresence, useScroll, useMotionValueEvent } from 'framer-motion';
import React, { useState } from 'react';

export const FloatingNav = ({
    navItems,
    className,
}: {
    navItems: {
        name: string;
        link: string;
        icon?: React.ReactNode;
    }[];
    className?: string;
}) => {
    const { scrollYProgress } = useScroll();
    const [visible, setVisible] = useState(true);

    useMotionValueEvent(scrollYProgress, 'change', (current) => {
        if (typeof current === 'number') {
            const direction = current - (scrollYProgress.getPrevious() ?? 0);
            if (scrollYProgress.get() < 0.05) {
                setVisible(true);
            } else {
                setVisible(direction < 0);
            }
        }
    });

    return (
        <AnimatePresence mode="wait">
            <motion.div
                initial={{ opacity: 1, y: -100 }}
                animate={{ y: visible ? 0 : -100, opacity: visible ? 1 : 0 }}
                transition={{ duration: 0.2 }}
                className={cn(
                    'fixed inset-x-0 top-10 z-[5000] mx-auto flex max-w-fit items-center justify-center space-x-4 rounded-full border border-white/[0.2] bg-black/50 px-8 py-4 shadow-[0px_2px_3px_-1px_rgba(0,0,0,0.1),0px_1px_0px_0px_rgba(25,28,33,0.02),0px_0px_0px_1px_rgba(25,28,33,0.08)] backdrop-blur-md',
                    className,
                )}
            >
                {navItems.map((navItem, idx) => (
                    <a
                        key={`link-${idx}`}
                        href={navItem.link}
                        className={cn(
                            'relative flex items-center space-x-1 text-on-dark-soft hover:text-on-dark transition-colors text-sm',
                        )}
                    >
                        {navItem.icon && <span className="block sm:hidden">{navItem.icon}</span>}
                        <span className="hidden sm:block text-sm">{navItem.name}</span>
                    </a>
                ))}
            </motion.div>
        </AnimatePresence>
    );
};
