export type WidgetHistoryMessage = {
    id?: string;
    cursor?: number;
    role: string;
    html?: string | null;
    created_at?: string | null;
};

export function historyMessageToBubble(msg: WidgetHistoryMessage): {
    role: 'user' | 'assistant';
    text: string;
    html: string | null;
    sentAt: string | null;
} {
    const role = msg.role === 'user' ? 'user' : 'assistant';
    const html = typeof msg.html === 'string' && msg.html.trim() ? msg.html : null;
    const sentAt = typeof msg.created_at === 'string' && msg.created_at.trim() ? msg.created_at : null;

    if (html) {
        const tmp = document.createElement('div');
        tmp.innerHTML = html;

        return { role, text: tmp.textContent || '', html: role === 'assistant' ? html : null, sentAt };
    }

    return { role, text: '', html: null, sentAt };
}
