import type { WidgetThemeId, WidgetThemeMode } from './types';

export type ThemeTokens = {
    '--ag-bg': string;
    '--ag-panel': string;
    '--ag-border': string;
    '--ag-text': string;
    '--ag-muted': string;
    '--ag-primary': string;
    '--ag-primary-hover': string;
    '--ag-primary-soft': string;
    '--ag-header-from': string;
    '--ag-header-to': string;
    '--ag-header-text': string;
    '--ag-header-muted': string;
    '--ag-user-bg': string;
    '--ag-user-fg': string;
    '--ag-send-bg': string;
    '--ag-send-hover': string;
    '--ag-shadow': string;
    '--ag-glow': string;
    '--ag-radius': string;
};

export type ThemePreset = {
    id: WidgetThemeId;
    label: string;
    swatch: string;
    tokens: ThemeTokens;
};

type ChromeTokens = Omit<
    ThemeTokens,
    | '--ag-primary'
    | '--ag-primary-hover'
    | '--ag-primary-soft'
    | '--ag-header-from'
    | '--ag-header-to'
    | '--ag-user-bg'
    | '--ag-user-fg'
    | '--ag-send-bg'
    | '--ag-send-hover'
>;

const LIGHT_CHROME: ChromeTokens = {
    '--ag-bg': '#f4f4f5',
    '--ag-panel': '#ffffff',
    '--ag-border': '#e5e5e7',
    '--ag-text': '#1d1d1f',
    '--ag-muted': '#6e6e73',
    '--ag-header-text': '#ffffff',
    '--ag-header-muted': 'rgba(255,255,255,0.7)',
    '--ag-shadow': '0 20px 50px rgba(15, 23, 42, 0.14), 0 4px 14px rgba(15, 23, 42, 0.06)',
    '--ag-glow': '0 10px 28px color-mix(in srgb, var(--ag-primary) 28%, rgba(15, 23, 42, 0.12))',
    '--ag-radius': '20px',
};

const DARK_CHROME: ChromeTokens = {
    '--ag-bg': '#111113',
    '--ag-panel': '#1c1c1f',
    '--ag-border': '#2c2c31',
    '--ag-text': '#f4f4f5',
    '--ag-muted': '#8e8e93',
    '--ag-header-text': '#ffffff',
    '--ag-header-muted': 'rgba(255,255,255,0.62)',
    '--ag-shadow': '0 24px 56px rgba(0, 0, 0, 0.45), 0 4px 14px rgba(0, 0, 0, 0.22)',
    '--ag-glow': '0 10px 28px rgba(0, 0, 0, 0.35)',
    '--ag-radius': '20px',
};

function hexToRgb(hex: string): string {
    const raw = hex.replace('#', '');
    const value = raw.length === 3 ? raw.split('').map((part) => part + part).join('') : raw;
    const red = parseInt(value.slice(0, 2), 16);
    const green = parseInt(value.slice(2, 4), 16);
    const blue = parseInt(value.slice(4, 6), 16);

    return `${red}, ${green}, ${blue}`;
}

function theme(
    id: WidgetThemeId,
    label: string,
    primary: string,
    hover: string,
    mode: 'light' | 'dark' = 'light',
    extras: Partial<ThemeTokens> = {},
): ThemePreset {
    const chrome = mode === 'dark' ? DARK_CHROME : LIGHT_CHROME;
    const rgb = hexToRgb(primary);

    return {
        id,
        label,
        swatch: primary,
        tokens: {
            ...chrome,
            '--ag-primary': primary,
            '--ag-primary-hover': hover,
            '--ag-primary-soft': `rgba(${rgb}, 0.08)`,
            '--ag-header-from': hover,
            '--ag-header-to': primary,
            '--ag-user-bg': mode === 'dark' ? `rgba(${rgb}, 0.22)` : `rgba(${rgb}, 0.12)`,
            '--ag-user-fg': mode === 'dark' ? '#ffffff' : hover,
            '--ag-send-bg': primary,
            '--ag-send-hover': hover,
            ...extras,
        },
    };
}

export const THEME_PRESETS: ThemePreset[] = [
    theme('aurora', 'Aurora', '#4f46e5', '#4338ca'),
    theme('midnight', 'Midnight', '#2563eb', '#1d4ed8', 'dark'),
    theme('ocean', 'Ocean', '#0f766e', '#0d5f59'),
    theme('forest', 'Forest', '#1b7a4e', '#166534'),
    theme('sunset', 'Sunset', '#c2410c', '#9a3412'),
    theme('rose', 'Rose', '#be185d', '#9d174d'),
    theme('gold', 'Gold', '#b45309', '#92400e'),
    theme('arctic', 'Arctic', '#0369a1', '#075985'),
    theme('graphite', 'Graphite', '#3f3f46', '#27272a'),
    theme('ember', 'Ember', '#7c3aed', '#6d28d9', 'dark'),
    theme('slate', 'Slate', '#475569', '#334155', 'dark'),
    theme('sand', 'Sand', '#92683a', '#78552e'),
    theme('lime', 'Lime', '#4d7c0f', '#3f6212'),
    theme('coral', 'Coral', '#c2410c', '#9a3412'),
    theme('indigo', 'Indigo', '#3730a3', '#312e81'),
    theme('mocha', 'Mocha', '#a16207', '#854d0e', 'dark'),
    theme('mint', 'Mint', '#0f766e', '#0d5f59'),
    theme('crimson', 'Crimson', '#b91c1c', '#991b1b', 'dark'),
    theme('sky', 'Sky', '#0369a1', '#075985'),
    theme('neon', 'Neon', '#0891b2', '#0e7490', 'dark'),
    theme('isnaad', 'Isnaad', '#c02526', '#9e1e1f', 'light', {
        '--ag-bg': '#f6f6f6',
        '--ag-border': '#ececec',
        '--ag-text': '#343434',
        '--ag-muted': '#82868b',
    }),
    theme('techsup', 'TechSup', '#6C075D', '#4A0540', 'light', {
        '--ag-bg': '#f7f5f7',
        '--ag-border': '#eee6ed',
        '--ag-text': '#2a1227',
        '--ag-muted': '#7a6676',
    }),
];

const aliases: Record<string, WidgetThemeId> = {
    light: 'aurora',
    dark: 'midnight',
    brand: 'ember',
    system: 'aurora',
};

export function isThemeId(value: string): value is WidgetThemeId {
    return THEME_PRESETS.some((item) => item.id === value);
}

export function resolveThemeId(mode: WidgetThemeMode | string | undefined): WidgetThemeId {
    if (mode === 'system' || mode === undefined) {
        return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'midnight' : 'aurora';
    }
    if (isThemeId(mode)) {
        return mode;
    }

    return aliases[mode] ?? 'aurora';
}

export function themeById(id: WidgetThemeId): ThemePreset {
    return THEME_PRESETS.find((item) => item.id === id) ?? THEME_PRESETS[0];
}

export function themeVarsFromConfig(themeConfig: Record<string, unknown> | undefined): Record<string, string> {
    if (!themeConfig) {
        return {};
    }
    const custom = themeConfig.custom;
    if (custom && typeof custom === 'object' && !Array.isArray(custom)) {
        const out: Record<string, string> = {};
        Object.entries(custom as Record<string, unknown>).forEach(([key, value]) => {
            if (typeof value === 'string' && value !== '') {
                const name = key.startsWith('--') ? key : `--ag-${key.replace(/_/g, '-')}`;
                out[name] = value;
            }
        });

        return out;
    }

    return {};
}

export function modeFromConfig(
    initMode: WidgetThemeMode | string,
    themeConfig: Record<string, unknown> | undefined,
): WidgetThemeMode {
    const def = themeConfig?.default ?? themeConfig?.mode;
    if (typeof def === 'string' && (isThemeId(def) || def === 'system' || def === 'light' || def === 'dark' || def === 'brand')) {
        return def as WidgetThemeMode;
    }

    return initMode as WidgetThemeMode;
}

export function applyTheme(
    host: HTMLElement,
    mode: WidgetThemeMode | string,
    custom?: Record<string, string>,
): WidgetThemeId {
    const id = resolveThemeId(mode);
    const tokens = { ...themeById(id).tokens, ...custom };
    Object.entries(tokens).forEach(([key, value]) => host.style.setProperty(key, value));
    host.dataset.theme = id;

    return id;
}
