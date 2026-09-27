export type RealtimeEvent = {
    id: number;
    event: string;
    payload: Record<string, unknown>;
};

export type WidgetRealtimeConnection = {
    start(): void;
    stop(): void;
};
