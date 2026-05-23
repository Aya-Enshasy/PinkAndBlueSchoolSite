let audioCtx;

function context() {
    if (!audioCtx) {
        audioCtx = new (window.AudioContext || window.webkitAudioContext)();
    }
    return audioCtx;
}

function tone(freq, duration = 0.12, gainValue = 0.02) {
    const ctx = context();
    const osc = ctx.createOscillator();
    const gain = ctx.createGain();

    osc.frequency.value = freq;
    osc.type = 'triangle';
    gain.gain.value = gainValue;
    osc.connect(gain);
    gain.connect(ctx.destination);
    osc.start();
    osc.stop(ctx.currentTime + duration);
}

export function playSound(type) {
    try {
        if (type === 'locked') {
            tone(190, 0.11);
            window.setTimeout(() => tone(150, 0.09), 80);
            return;
        }

        if (type === 'win') {
            tone(523, 0.12);
            window.setTimeout(() => tone(659, 0.12), 90);
            window.setTimeout(() => tone(784, 0.15), 180);
            return;
        }

        if (type === 'start') {
            tone(392, 0.08);
            return;
        }

        tone(440, 0.07);
    } catch {
        // Ignore audio failures in browsers that block autoplay.
    }
}

