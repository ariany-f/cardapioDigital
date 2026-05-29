/** Cor do tenant e contraste de texto para botões do cardápio. */

export function normalizeHex(color, fallback = '#c2410c') {
    if (!color || typeof color !== 'string') return fallback;
    const trimmed = color.trim();
    if (/^#[0-9a-fA-F]{6}$/.test(trimmed)) return trimmed;
    if (/^#[0-9a-fA-F]{3}$/.test(trimmed)) {
        return `#${trimmed[1]}${trimmed[1]}${trimmed[2]}${trimmed[2]}${trimmed[3]}${trimmed[3]}`;
    }
    return fallback;
}

export function textOnBackground(hex) {
    const color = normalizeHex(hex);
    const n = parseInt(color.slice(1), 16);
    const r = (n >> 16) & 255;
    const g = (n >> 8) & 255;
    const b = n & 255;
    const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;
    return luminance > 0.62 ? '#1c1917' : '#ffffff';
}

export function menuThemeVars(primary) {
    const main = normalizeHex(primary);
    return {
        '--menu-primary': main,
        '--menu-primary-soft': `color-mix(in srgb, ${main} 14%, white)`,
        '--menu-primary-hover': `color-mix(in srgb, ${main} 82%, black)`,
    };
}
