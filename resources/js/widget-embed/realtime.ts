import type { WidgetApiClient } from './api';

import type { RealtimeEvent } from './realtime-types';

export type { RealtimeEvent } from './realtime-types';

export class WidgetRealtimePoller {
    private timer: number | null = null;
    private sinceId = 0;

    constructor(
        private api: WidgetApiClient,
        private conversationId: string,
        private intervalMs: number,
        private onEvent: (event: RealtimeEvent) => void,
        initialSinceId = 0,
    ) {
        this.sinceId = initialSinceId;
    }

    start(): void {
        this.stop();
        const tick = async () => {
            try {
                const events = await this.api.pollRealtime(this.conversationId, this.sinceId);
                for (const ev of events) {
                    this.sinceId = Math.max(this.sinceId, ev.id);
                    this.onEvent(ev);
                }
            } catch {
                /* backoff silently */
            }
        };
        void tick();
        this.timer = window.setInterval(() => void tick(), this.intervalMs);
    }

    stop(): void {
        if (this.timer !== null) {
            window.clearInterval(this.timer);
            this.timer = null;
        }
    }
}
