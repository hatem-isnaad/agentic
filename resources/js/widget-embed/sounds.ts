let audioCtx: AudioContext | null = null;

function ctx(): AudioContext {
    if (!audioCtx) {
        audioCtx = new AudioContext();
    }

    return audioCtx;
}

/** Subtle UI beeps — no external assets required */
export function playWidgetSound(kind: 'send' | 'receive', enabled: boolean): void {
    if (!enabled) {
        return;
    }
    try {
        const c = ctx();
        const osc = c.createOscillator();
        const gain = c.createGain();
        osc.connect(gain);
        gain.connect(c.destination);
        osc.frequency.value = kind === 'send' ? 520 : 640;
        gain.gain.value = 0.04;
        osc.start();
        gain.gain.exponentialRampToValueAtTime(0.0001, c.currentTime + 0.12);
        osc.stop(c.currentTime + 0.14);
    } catch {
        /* ignore */
    }
}
