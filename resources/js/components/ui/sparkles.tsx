'use client';

import { cn } from '@/lib/utils';
import React, { useId } from 'react';
import Particles, { ParticlesProvider, useParticlesProvider } from '@tsparticles/react';
import { loadSlim } from '@tsparticles/slim';
import type { Container, ISourceOptions } from '@tsparticles/engine';

type ParticlesProps = {
    id?: string;
    className?: string;
    background?: string;
    particleColor?: string;
    minSize?: number;
    maxSize?: number;
    speed?: number;
    particleDensity?: number;
};

function SparklesInner({
    id,
    className,
    background = 'transparent',
    minSize = 0.4,
    maxSize = 1,
    speed = 1,
    particleColor = '#FFFFFF',
    particleDensity = 120,
}: ParticlesProps) {
    const { loaded } = useParticlesProvider();
    const generatedId = useId();

    const particlesLoaded = async (_container?: Container): Promise<void> => {};

    const options: ISourceOptions = {
        background: { color: { value: background } },
        fullScreen: { enable: false, zIndex: 1 },
        fpsLimit: 120,
        interactivity: {
            events: {
                onClick: { enable: true, mode: 'push' },
                onHover: { enable: false, mode: 'repulse' },
                resize: { enable: true },
            },
            modes: {
                push: { quantity: 4 },
                repulse: { distance: 200, duration: 0.4 },
            },
        },
        particles: {
            color: { value: particleColor },
            move: {
                direction: 'none',
                enable: true,
                outModes: { default: 'out' },
                random: false,
                speed: { min: 0.1, max: speed },
                straight: false,
            },
            number: {
                density: { enable: true, width: 400, height: 400 },
                value: particleDensity,
            },
            opacity: {
                value: { min: 0.1, max: 1 },
                animation: { enable: true, speed: speed, sync: false },
            },
            shape: { type: 'circle' },
            size: { value: { min: minSize, max: maxSize } },
        },
        detectRetina: true,
    };

    if (!loaded) return null;

    return (
        <div className={cn('opacity-0 animate-fade-in', className)}>
            <Particles
                id={id || generatedId}
                className="h-full w-full"
                particlesLoaded={particlesLoaded}
                options={options}
            />
        </div>
    );
}

export const SparklesCore = (props: ParticlesProps) => {
    return (
        <ParticlesProvider init={async (engine) => { await loadSlim(engine); }}>
            <SparklesInner {...props} />
        </ParticlesProvider>
    );
};
