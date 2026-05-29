import { ref } from 'vue';

const permissionAsked = ref(false);

export function playNewChatSound() {
    if (typeof window === 'undefined') return;

    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.type = 'sine';
        osc.frequency.value = 740;
        gain.gain.setValueAtTime(0.0001, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.15, ctx.currentTime + 0.02);
        gain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.18);
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.start();
        osc.stop(ctx.currentTime + 0.2);
    } catch {
        /* autoplay policy */
    }
}

export async function ensureChatNotificationPermission() {
    if (typeof window === 'undefined' || !('Notification' in window)) {
        return false;
    }
    if (Notification.permission === 'granted') {
        return true;
    }
    if (Notification.permission === 'denied' || permissionAsked.value) {
        return false;
    }
    permissionAsked.value = true;
    const result = await Notification.requestPermission();
    return result === 'granted';
}

export function notifyNewChatMessage({ title, body, tag, onClick }) {
    if (typeof document !== 'undefined' && document.hidden && 'Notification' in window && Notification.permission === 'granted') {
        const n = new Notification(title, { body, tag, icon: '/favicon.ico' });
        n.onclick = () => {
            window.focus();
            n.close();
            onClick?.();
        };
        return;
    }

    flashDocumentTitle(title);
}

let titleFlashTimer = null;
let originalTitle = null;

export function flashDocumentTitle(prefix) {
    if (typeof document === 'undefined') return;

    if (!originalTitle) {
        originalTitle = document.title;
    }

    let toggle = false;
    clearInterval(titleFlashTimer);
    titleFlashTimer = setInterval(() => {
        document.title = toggle ? originalTitle : `(${prefix}) ${originalTitle}`;
        toggle = !toggle;
    }, 1200);

    const stop = () => {
        clearInterval(titleFlashTimer);
        titleFlashTimer = null;
        if (originalTitle) {
            document.title = originalTitle;
        }
        document.removeEventListener('visibilitychange', onVisible);
    };

    const onVisible = () => {
        if (!document.hidden) {
            stop();
        }
    };

    document.addEventListener('visibilitychange', onVisible);
}
