import type { RealtimeEvent } from './realtime-types';

/** Pusher/polling: assistant.typing { active: boolean } */
export function typingActiveFromEvent(ev: RealtimeEvent): boolean | null {
    if (ev.event !== 'assistant.typing') {
        return null;
    }

    return ev.payload.active === true;
}

export function assistantMessageFromEvent(ev: RealtimeEvent): { text: string; html: string | null } | null {
    if (ev.event === 'message.failed') {
        const error = String(ev.payload.error ?? 'Something went wrong.');

        return { text: error, html: null };
    }

    if (ev.event === 'message.created' && ev.payload.message && typeof ev.payload.message === 'object') {
        return messageRecordToReply(ev.payload.message as Record<string, unknown>);
    }

    if (ev.event === 'message.assistant' || ev.event === 'assistant.message') {
        const text = String(ev.payload.text ?? ev.payload.message ?? '');
        if (!text) {
            return null;
        }

        return { text, html: null };
    }

    return null;
}

function messageRecordToReply(msg: Record<string, unknown>): { text: string; html: string | null } | null {
    if (typeof msg.html === 'string' && msg.html.trim()) {
        const tmp = document.createElement('div');
        tmp.innerHTML = msg.html;

        return { text: tmp.textContent || '', html: msg.html };
    }

    if (typeof msg.text === 'string' && msg.text.trim()) {
        return { text: msg.text, html: null };
    }

    return null;
}

export function streamDeltaFromEvent(ev: RealtimeEvent): string | null {
    if (ev.event !== 'message.delta') {
        return null;
    }

    const delta = ev.payload.delta;

    return typeof delta === 'string' && delta !== '' ? delta : null;
}
