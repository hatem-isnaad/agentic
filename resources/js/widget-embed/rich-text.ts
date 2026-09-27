const ALLOWED_TAGS = new Set([
    'A', 'ARTICLE', 'BLOCKQUOTE', 'BR', 'CODE', 'DIV', 'EM', 'FOOTER', 'H1', 'H2', 'H3', 'H4',
    'HEADER', 'HR', 'I', 'LI', 'OL', 'P', 'PRE', 'SMALL', 'SPAN', 'STRONG', 'B', 'TABLE',
    'TBODY', 'TD', 'TH', 'THEAD', 'TR', 'UL',
]);

const ALLOWED_ATTRS: Record<string, string[]> = {
    A: ['href', 'title', 'rel', 'target'],
    CODE: ['class'],
    TH: ['colspan', 'rowspan'],
    TD: ['colspan', 'rowspan'],
};

export function assistantBubbleHtml(html: string | null, text: string): string {
    const source = (html && html.trim()) || text;
    if (!source.trim()) {
        return '';
    }

    if (looksLikeRichHtml(source)) {
        return sanitizeAssistantHtml(source);
    }

    return sanitizeAssistantHtml(markdownToSafeHtml(plainFromStoredHtml(source)));
}

function looksLikeRichHtml(value: string): boolean {
    return /<(?!br\s*\/?>)(p|ul|ol|li|table|thead|tbody|tr|th|td|h[1-6]|pre|code|blockquote|strong|em|article)\b/i.test(value);
}

function plainFromStoredHtml(value: string): string {
    const tmp = document.createElement('div');
    tmp.innerHTML = value.replace(/<br\s*\/?>/gi, '\n');

    return tmp.textContent ?? value;
}

function markdownToSafeHtml(markdown: string): string {
    let value = escapeHtml(markdown.trim());
    if (!value) {
        return '';
    }

    value = value.replace(/```([a-zA-Z0-9_-]*)\n([\s\S]*?)```/g, (_m, lang, code) => {
        const cls = lang ? ` class="language-${lang}"` : '';

        return `<pre><code${cls}>${code}</code></pre>`;
    });
    value = renderTables(value);
    value = value.replace(/^### (.+)$/gm, '<h3>$1</h3>');
    value = value.replace(/^## (.+)$/gm, '<h2>$1</h2>');
    value = value.replace(/^# (.+)$/gm, '<h2>$1</h2>');
    value = renderLists(value);
    value = value.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
    value = value.replace(/__(.+?)__/g, '<strong>$1</strong>');
    value = value.replace(/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/g, '<em>$1</em>');
    value = value.replace(/`([^`]+)`/g, '<code>$1</code>');
    value = value.replace(/\n{2,}/g, '</p><p>');
    value = value.replace(/\n/g, '<br>');

    if (!/<(p|h[1-6]|ul|ol|table|pre)\b/i.test(value)) {
        return `<p>${value}</p>`;
    }

    return value;
}

function renderTables(value: string): string {
    return value.replace(/(?:^|\n)(\|.+\|(?:\n\|[-:| ]+\|)+(?:\n\|.+\|)+)/g, (_m, block: string) => {
        const lines = block.trim().split('\n').map((line) => line.trim());
        const headers = splitCells(lines.shift() ?? '');
        lines.shift();
        const rows = lines.map(splitCells);
        const head = headers.map((cell) => `<th>${cell}</th>`).join('');
        const body = rows.map((row) => `<tr>${row.map((cell) => `<td>${cell}</td>`).join('')}</tr>`).join('');

        return `<table><thead><tr>${head}</tr></thead><tbody>${body}</tbody></table>`;
    });
}

function splitCells(line: string): string[] {
    return line.replace(/^\||\|$/g, '').split('|').map((cell) => cell.trim());
}

function renderLists(value: string): string {
    value = value.replace(/(?:^|\n)((?:[-*+] .+(?:\n|$))+)/g, (_m, block: string) => {
        const items = block
            .trim()
            .split('\n')
            .map((line) => line.replace(/^[-*+] /, ''))
            .map((item) => `<li>${item}</li>`)
            .join('');

        return `\n<ul>${items}</ul>\n`;
    });

    return value.replace(/(?:^|\n)((?:\d+\. .+(?:\n|$))+)/g, (_m, block: string) => {
        const items = block
            .trim()
            .split('\n')
            .map((line) => line.replace(/^\d+\. /, ''))
            .map((item) => `<li>${item}</li>`)
            .join('');

        return `\n<ol>${items}</ol>\n`;
    });
}

function escapeHtml(value: string): string {
    return value
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
}

export function sanitizeAssistantHtml(html: string): string {
    const root = document.createElement('div');
    root.innerHTML = html;
    sanitizeNode(root);

    return root.innerHTML;
}

function sanitizeNode(node: ParentNode): void {
    Array.from(node.childNodes).forEach((child) => {
        if (child.nodeType === Node.COMMENT_NODE) {
            child.remove();

            return;
        }
        if (child.nodeType !== Node.ELEMENT_NODE) {
            return;
        }

        const el = child as HTMLElement;
        const tag = el.tagName;
        if (!ALLOWED_TAGS.has(tag)) {
            const text = document.createTextNode(el.textContent ?? '');
            el.replaceWith(text);

            return;
        }

        Array.from(el.attributes).forEach((attr) => {
            const allowed = ALLOWED_ATTRS[tag] ?? [];
            if (!allowed.includes(attr.name.toLowerCase())) {
                el.removeAttribute(attr.name);
            }
        });

        if (tag === 'A') {
            const href = el.getAttribute('href') ?? '';
            if (!/^(https?:|mailto:|#)/i.test(href)) {
                el.removeAttribute('href');
            }
            el.setAttribute('rel', 'noopener noreferrer');
            el.setAttribute('target', '_blank');
        }

        sanitizeNode(el);
    });
}
