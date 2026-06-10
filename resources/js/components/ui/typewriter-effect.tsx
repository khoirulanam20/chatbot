'use client';

import { cn } from '@/lib/utils';
import { motion, stagger, useAnimate, useInView } from 'framer-motion';
import { useEffect } from 'react';

export const TypewriterEffect = ({
    words,
    className,
    cursorClassName,
}: {
    words: {
        text: string;
        className?: string;
    }[];
    className?: string;
    cursorClassName?: string;
}) => {
    const wordsArray = words.map((word) => ({
        ...word,
        text: word.text.split(''),
    }));

    const [scope, animate] = useAnimate();
    const isInView = useInView(scope);

    useEffect(() => {
        if (isInView) {
            animate(
                'span',
                { display: 'inline-block', opacity: 1, width: 'fit-content' },
                { duration: 0.3, delay: stagger(0.1), ease: 'easeInOut' },
            );
        }
    }, [isInView, animate]);

    const renderWords = () => {
        return (
            <motion.div ref={scope} className="inline">
                {wordsArray.map((word, idx) => (
                    <div key={`word-${idx}`} className="inline-block">
                        {word.text.map((char, index) => (
                            <motion.span
                                initial={{}}
                                key={`char-${index}`}
                                className={cn(
                                    'dark:text-white text-black opacity-0 hidden',
                                    word.className,
                                )}
                            >
                                {char}
                            </motion.span>
                        ))}
                        &nbsp;
                    </div>
                ))}
            </motion.div>
        );
    };

    return (
        <div className={cn('font-bold text-4xl sm:text-5xl lg:text-[3.5rem] lg:leading-[1.1]', className)}>
            {renderWords()}
            <motion.span
                initial={{ opacity: 0 }}
                animate={{ opacity: 1 }}
                transition={{ duration: 0.8, repeat: Infinity, repeatType: 'reverse' }}
                className={cn(
                    'inline-block rounded-sm w-[4px] h-8 md:h-10 lg:h-12 bg-primary ml-1',
                    cursorClassName,
                )}
            />
        </div>
    );
};

export const TypewriterEffectSmooth = ({
    words,
    className,
    cursorClassName,
}: {
    words: {
        text: string;
        className?: string;
    }[];
    className?: string;
    cursorClassName?: string;
}) => {
    const wordsArray = words.map((word) => ({
        ...word,
        text: word.text.split(''),
    }));

    const renderWords = () => (
        <div>
            {wordsArray.map((word, idx) => (
                <div key={`word-${idx}`} className="inline-block">
                    {word.text.map((char, index) => (
                        <span
                            key={`char-${index}`}
                            className={cn('dark:text-white text-black', word.className)}
                        >
                            {char}
                        </span>
                    ))}
                    &nbsp;
                </div>
            ))}
        </div>
    );

    return (
        <div className={cn('font-bold text-xl md:text-2xl text-center', className)}>
            {renderWords()}
            <motion.span
                initial={{ opacity: 0 }}
                animate={{ opacity: 1 }}
                transition={{ duration: 0.8, repeat: Infinity, repeatType: 'reverse' }}
                className={cn('inline-block rounded-sm w-[4px] h-4 md:h-6 lg:h-8 bg-primary', cursorClassName)}
            />
        </div>
    );
};
