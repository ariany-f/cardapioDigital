/** Alerta sonoro curto para novos pedidos no KDS/admin. */
export function playNewOrderSound() {
    if (typeof window === 'undefined') return;

    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const playTone = (freq, start, duration) => {
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'sine';
            osc.frequency.value = freq;
            gain.gain.setValueAtTime(0.0001, start);
            gain.gain.exponentialRampToValueAtTime(0.2, start + 0.02);
            gain.gain.exponentialRampToValueAtTime(0.0001, start + duration);
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start(start);
            osc.stop(start + duration);
        };
        const t = ctx.currentTime;
        playTone(880, t, 0.15);
        playTone(1100, t + 0.18, 0.2);
    } catch {
        /* autoplay policy ou browser sem suporte */
    }
}
