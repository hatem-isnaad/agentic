import hljs from 'highlight.js/lib/core';
import bash from 'highlight.js/lib/languages/bash';
import ini from 'highlight.js/lib/languages/ini';
import php from 'highlight.js/lib/languages/php';
import plaintext from 'highlight.js/lib/languages/plaintext';

let registered = false;

export function ensureDocHighlightLanguages(): void {
    if (registered) {
        return;
    }
    hljs.registerLanguage('bash', bash);
    hljs.registerLanguage('shell', bash);
    hljs.registerLanguage('php', php);
    hljs.registerLanguage('ini', ini);
    hljs.registerLanguage('plaintext', plaintext);
    registered = true;
}

export type DocCodeLanguage = 'bash' | 'php' | 'ini' | 'plaintext';

export function inferDocCodeLanguage(code: string, title?: string): DocCodeLanguage {
    const t = (title ?? '').toLowerCase();
    if (t.includes('php') || t.includes('host code')) {
        return 'php';
    }
    if (t.includes('.env') || t.includes('environment')) {
        return 'ini';
    }
    if (t.includes('terminal') || t.includes('cli') || t.includes('script') || t.includes('artisan')) {
        return 'bash';
    }
    if (code.trim().startsWith('<?php') || code.includes("'code_tools'")) {
        return 'php';
    }
    if (/^AGENTIC_|^[A-Z][A-Z0-9_]*=/.test(code.trim())) {
        return 'ini';
    }
    if (code.includes('php artisan') || code.includes('composer ')) {
        return 'bash';
    }

    return 'plaintext';
}

export function highlightDocCode(code: string, language: DocCodeLanguage): string {
    ensureDocHighlightLanguages();
    try {
        return hljs.highlight(code, { language }).value;
    } catch {
        return hljs.highlight(code, { language: 'plaintext' }).value;
    }
}
