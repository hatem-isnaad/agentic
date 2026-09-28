import Pusher from 'pusher-js';
import type { RealtimeEvent, WidgetRealtimeConnection } from './realtime-types';

type PusherRealtimeConfig = {
    key: string;
    cluster: string;
    channelPrefix: string;
};

type PusherOptions = {
    debug?: boolean;
    onConnectionState?: (state: string) => void;
    onSubscribed?: () => void;
};

const sharedPusherClients = new Map<string, Pusher>();

function sharedPusher(key: string, cluster: string): Pusher {
    const cacheKey = `${key}::${cluster}`;
    let client = sharedPusherClients.get(cacheKey);
    if (!client) {
        client = new Pusher(key, {
            cluster,
            enabledTransports: ['ws', 'wss'],
        });
        sharedPusherClients.set(cacheKey, client);
    }

    return client;
}

export function disconnectWidgetPusher(key: string, cluster: string): void {
    const cacheKey = `${key}::${cluster}`;
    const client = sharedPusherClients.get(cacheKey);
    client?.disconnect();
    sharedPusherClients.delete(cacheKey);
}

export class WidgetRealtimePusher implements WidgetRealtimeConnection {
    private pusher: Pusher | null = null;
    private channel: ReturnType<Pusher['subscribe']> | null = null;
    private subscribed = false;
    private channelName = '';
    private readonly onStateChange = (states: { current: string }) => {
        if (states.current === 'connected' && this.subscribed) {
            this.options.onConnectionState?.('connected');
        } else if (states.current === 'connecting') {
            this.options.onConnectionState?.('connecting');
        } else if (states.current === 'unavailable' || states.current === 'failed') {
            this.subscribed = false;
            this.options.onConnectionState?.('disconnected');
        }
        if (this.options.debug) {
            console.info('[AgenticChat] Pusher state:', states.current);
        }
    };

    constructor(
        private conversationId: string,
        private config: PusherRealtimeConfig,
        private onEvent: (event: RealtimeEvent) => void,
        private options: PusherOptions = {},
    ) {}

    isSubscribed(): boolean {
        return this.subscribed;
    }

    start(): void {
        this.stop();
        this.subscribed = false;

        this.pusher = sharedPusher(this.config.key, this.config.cluster);
        this.channelName = `${this.config.channelPrefix}.${this.conversationId}`;

        if (this.options.debug) {
            console.info('[AgenticChat] Pusher connecting…', {
                cluster: this.config.cluster,
                channel: this.channelName,
            });
        }

        this.pusher.connection.bind('state_change', this.onStateChange);

        this.channel = this.pusher.subscribe(this.channelName);

        this.channel.bind('pusher:subscription_succeeded', () => {
            this.subscribed = true;
            this.options.onConnectionState?.('connected');
            this.options.onSubscribed?.();
            if (this.options.debug) {
                console.info('[AgenticChat] Pusher subscribed:', this.channelName);
            }
        });

        this.channel.bind('pusher:subscription_error', () => {
            this.subscribed = false;
            this.options.onConnectionState?.('disconnected');
            if (this.options.debug) {
                console.warn('[AgenticChat] Pusher subscription error');
            }
        });

        const forward = (event: string, payload: Record<string, unknown>) => {
            this.onEvent({
                id: 0,
                event,
                payload,
            });
        };

        this.channel.bind('message.created', (payload: Record<string, unknown>) => {
            forward('message.created', payload);
        });
        this.channel.bind('message.assistant', (payload: Record<string, unknown>) => {
            forward('message.assistant', payload);
        });
        this.channel.bind('message.failed', (payload: Record<string, unknown>) => {
            forward('message.failed', payload);
        });
        this.channel.bind('assistant.typing', (payload: Record<string, unknown>) => {
            forward('assistant.typing', payload);
        });
        this.channel.bind('message.delta', (payload: Record<string, unknown>) => {
            forward('message.delta', payload);
        });
    }

    stop(): void {
        this.subscribed = false;
        if (this.pusher) {
            this.pusher.connection.unbind('state_change', this.onStateChange);
        }
        if (this.channel) {
            this.channel.unbind_all();
            this.pusher?.unsubscribe(this.channel.name);
            this.channel = null;
        }
        this.pusher = null;
    }
}
