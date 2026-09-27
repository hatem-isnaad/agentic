import type { AgenticWidgetInit } from './types';

const PLACEHOLDER_PATTERNS = [
    /^INJECT_FROM_SERVER$/i,
    /^FROM_SERVER_ENV$/i,
    /^YOUR_EMBED_TOKEN$/i,
    /^wgt_\.\.\./i,
    /INJECT/i,
    /REPLACE_ME/i,
    /CHANGEME/i,
];

export function isPlaceholderEmbedToken(token: string): boolean {
    const t = token.trim();

    return PLACEHOLDER_PATTERNS.some((re) => re.test(t));
}

/**
 * Client-side gate before any widget UI is mounted.
 * Sanctum bearerToken satisfies auth without wgt_….
 */
export function embedCredentialError(config: AgenticWidgetInit): string | null {
    const token = typeof config.embedToken === 'string' ? config.embedToken.trim() : '';

    if (token === '') {
        return 'AgenticChat.init: embedToken (wgt_…) is required.';
    }

    if (isPlaceholderEmbedToken(token)) {
        return 'AgenticChat.init: embedToken is still a placeholder — set a real wgt_… value from your server .env or embed token create.';
    }

    if (!token.startsWith('wgt_') || token.length < 12) {
        return 'AgenticChat.init: invalid embed token — expected a value starting with wgt_.';
    }

    return null;
}
