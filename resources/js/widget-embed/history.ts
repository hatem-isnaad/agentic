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
        const text = plainTextExcludingAttachments(html);

        return { role, text, html, sentAt };
    }

    return { role, text: '', html: null, sentAt };
}

/** Avoid duplicating filenames from <img alt> / figcaption in plain-text bubbles. */
function plainTextExcludingAttachments(html: string): string {
    const tmp = document.createElement('div');
    tmp.innerHTML = html;
    tmp.querySelectorAll('figure.ag-attach, .ag-attach-file').forEach((el) => el.remove());

    return (tmp.textContent || '').trim();
}
