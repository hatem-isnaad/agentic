import type { WidgetApiClient } from './api';
import type { RealtimeEvent, WidgetRealtimeConnection } from './realtime-types';
import { WidgetRealtimePusher } from './realtime-pusher';

export type RealtimeFactoryOptions = {
    debug?: boolean;
    onPusherState?: (state: string) => void;
    onPusherSubscribed?: () => void;
};

/**
 * Creates exactly one realtime transport for the server-declared driver.
 * No fallback: pusher never degrades to HTTP polling.
 */
export function createRealtimeConnection(
    driver: string,
    api: WidgetApiClient,
    conversationId: string,
    pollMs: number,
    channelPrefix: string,
    pusher: { key?: string | null; cluster?: string | null } | undefined,
    onEvent: (event: RealtimeEvent) => void,
    initialSinceId = 0,
    factoryOptions: RealtimeFactoryOptions = {},
): WidgetRealtimeConnection | null {
    if (driver === 'null' || driver === '') {
        return null;
    }

    if (driver === 'pusher') {
        if (!pusher?.key || !pusher.cluster) {
            if (factoryOptions.debug) {
                console.warn(
                    '[AgenticChat] realtime.driver is pusher but key/cluster missing — realtime disabled (no polling fallback).',
                );
            }

            return null;
        }

        return new WidgetRealtimePusher(
            conversationId,
            {
                key: pusher.key,
                cluster: pusher.cluster,
                channelPrefix,
            },
            onEvent,
            {
                debug: factoryOptions.debug,
                onConnectionState: factoryOptions.onPusherState,
                onSubscribed: factoryOptions.onPusherSubscribed,
            },
        );
    }

    if (driver === 'polling') {
        return new PollingConnection(api, conversationId, pollMs, onEvent, initialSinceId);
    }

    if (factoryOptions.debug) {
        console.warn('[AgenticChat] Unsupported realtime driver:', driver);
    }

    return null;
}

/** Loaded only when config realtime.driver is polling (separate chunk). */
class PollingConnection implements WidgetRealtimeConnection {
    private inner: WidgetRealtimeConnection | null = null;
    private starting = false;

    constructor(
        private api: WidgetApiClient,
        private conversationId: string,
        private pollMs: number,
        private onEvent: (event: RealtimeEvent) => void,
        private initialSinceId: number,
    ) {}

    start(): void {
        if (this.inner || this.starting) {
            return;
        }
        this.starting = true;
        void import('./realtime').then(({ WidgetRealtimePoller }) => {
            this.inner = new WidgetRealtimePoller(
                this.api,
                this.conversationId,
                this.pollMs,
                this.onEvent,
                this.initialSinceId,
            );
            this.inner.start();
            this.starting = false;
        });
    }

    stop(): void {
        this.inner?.stop();
        this.inner = null;
        this.starting = false;
    }
}
