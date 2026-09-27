/** POST /messages JSON (wrapped in { data } or flat). */
export type WidgetMessageAck = {
    success?: boolean;
    pending?: boolean;
    conversation_id?: string;
    text?: string;
    error?: string;
    message?: Record<string, unknown>;
};

export function unwrapMessageResponse(json: unknown): WidgetMessageAck {
    if (!json || typeof json !== 'object') {
        return {};
    }

    const root = json as Record<string, unknown>;
    const inner = root.data;

    if (inner && typeof inner === 'object' && !Array.isArray(inner)) {
        return inner as WidgetMessageAck;
    }

    return root as WidgetMessageAck;
}

/** Async mode: HTTP ack only — never render as assistant text. */
export function isPendingMessageAck(data: WidgetMessageAck): boolean {
    if (data.pending === true) {
        return true;
    }

    return (
        data.success === true &&
        data.pending !== false &&
        data.conversation_id !== undefined &&
        data.conversation_id !== '' &&
        !hasAssistantPayload(data)
    );
}

function hasAssistantPayload(data: WidgetMessageAck): boolean {
    if (typeof data.text === 'string' && data.text.trim() !== '') {
        return true;
    }

    const msg = data.message;
    if (!msg || typeof msg !== 'object') {
        return false;
    }

    const html = (msg as Record<string, unknown>).html;
    const text = (msg as Record<string, unknown>).text;

    return (
        (typeof html === 'string' && html.trim() !== '') ||
        (typeof text === 'string' && text.trim() !== '')
    );
}

export function resolveAssistantReply(data: WidgetMessageAck): { text: string; html: string | null } | null {
    if (isPendingMessageAck(data)) {
        return null;
    }

    const msg = data.message;
    if (msg && typeof msg.html === 'string' && msg.html.trim()) {
        const tmp = document.createElement('div');
        tmp.innerHTML = msg.html;

        return { text: tmp.textContent || '', html: msg.html };
    }

    if (typeof data.text === 'string' && data.text.trim()) {
        return { text: data.text, html: null };
    }

    return null;
}
