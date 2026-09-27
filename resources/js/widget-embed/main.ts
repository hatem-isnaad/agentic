import { ensureGuestId, WidgetApiClient } from './api';
import { embedCredentialError } from './embed-credentials';
import { WidgetUi } from './widget-ui';
import type { AgenticWidgetInit } from './types';

let active: WidgetUi | null = null;

function defaultApiBase(): string {
    const el = document.querySelector<HTMLElement>('[data-agentic-widget-api]');

    return el?.dataset.agenticWidgetApi || '/api/agentic/widget';
}

class AgenticChat {
    /**
     * 1) Require wgt_… token
     * 2) GET /config validates token + host on server and returns UI settings
     * 3) Mount widget only after success
     */
    static async init(config: AgenticWidgetInit): Promise<WidgetUi | null> {
        if (!config.agent) {
            throw new Error('AgenticChat.init: agent is required');
        }

        const credErr = embedCredentialError(config);
        if (credErr) {
            console.error('[AgenticChat]', credErr);

            return null;
        }

        active?.destroy();

        const api = new WidgetApiClient({
            apiBase: config.apiBase ?? defaultApiBase(),
            embedToken: config.embedToken,
            bearerToken: config.bearerToken,
            userId: config.userId,
            guestId: ensureGuestId(),
        });

        const ui = new WidgetUi(config, api);
        const ok = await ui.bootstrap();
        if (!ok) {
            console.error(
                '[AgenticChat] Config failed — launcher is visible but chat needs a valid embedToken, origin, and guest access.',
            );

            return ui;
        }

        active = ui;

        return ui;
    }

    static destroy(): void {
        active?.destroy();
        active = null;
    }
}

export default AgenticChat;

export type { AgenticWidgetInit };
