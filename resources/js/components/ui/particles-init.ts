import type { Engine } from '@tsparticles/engine';
import { loadSlim } from '@tsparticles/slim';

export async function initParticlesEngine(engine: Engine): Promise<void> {
    await loadSlim(engine);
}
