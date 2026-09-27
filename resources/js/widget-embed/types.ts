export type WidgetThemeId =
    | 'aurora'
    | 'midnight'
    | 'ocean'
    | 'forest'
    | 'sunset'
    | 'rose'
    | 'gold'
    | 'arctic'
    | 'graphite'
    | 'ember'
    | 'slate'
    | 'sand'
    | 'lime'
    | 'coral'
    | 'indigo'
    | 'mocha'
    | 'mint'
    | 'crimson'
    | 'sky'
    | 'neon'
    | 'isnaad'
    | 'techsup';

export type WidgetThemeMode = WidgetThemeId | 'light' | 'dark' | 'system' | 'brand';

export type WidgetPosition = 'bottom-right' | 'bottom-left' | 'top-right' | 'top-left';

export type AgenticWidgetInit = {
    /** Published agent slug */
    agent: string;
    /** e.g. https://app.example.com/api/agentic/widget */
    apiBase?: string;
    /** wgt_… embed token (required when AGENTIC_WIDGET_EMBED_REQUIRE_TOKEN=true) */
    embedToken?: string;
    /** Sanctum personal access token for logged-in users */
    bearerToken?: string;
    /** Your site user id when embed token disallows guests (auth_required in config) */
    userId?: string;
    theme?: WidgetThemeMode;
    position?: WidgetPosition;
    locale?: string;
    /** Play subtle sounds on send/receive */
    sounds?: boolean;
    /** Open panel on load */
    open?: boolean;
    /** Resume conversation */
    conversationId?: string | null;
    zIndex?: number;
    /** Log Pusher subscribe / state to console */
    debug?: boolean;
};

export type WidgetConversationSummary = {
    id: string;
    preview?: string;
    last_message_at?: string | null;
    created_at?: string | null;
    updated_at?: string | null;
};

export type WidgetConfigResponse = {
    data: {
        agent: { slug: string; name: string } | string;
        theme?: { mode?: string; default?: string; custom?: Record<string, string> };
        locale?: string;
        welcome?: string;
        intake?: { welcome_message?: string | null };
        realtime?: {
            driver?: string;
            channel_prefix?: string;
            interval_ms?: number;
            pusher?: { key?: string | null; cluster?: string | null };
        };
        reply?: { mode?: 'sync' | 'async' };
        history?: { page_size?: number; max_page_size?: number };
        conversation?: { resume_after_hours?: number };
        embed?: {
            require_token?: boolean;
            guest_allowed?: boolean;
            auth_required?: boolean;
            sanctum_allowed?: boolean;
        };
    };
};
