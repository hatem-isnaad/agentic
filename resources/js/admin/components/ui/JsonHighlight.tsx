import hljs from 'highlight.js/lib/core';
import json from 'highlight.js/lib/languages/json';
import { useEffect, useRef } from 'react';
import 'highlight.js/styles/github.min.css';

hljs.registerLanguage('json', json);

type JsonHighlightProps = {
    data?: unknown;
    /** Alias for `data` (used across admin detail pages). */
    value?: unknown;
};

export function JsonHighlight({ data, value }: JsonHighlightProps) {
    const ref = useRef<HTMLElement>(null);
    const payload = data ?? value ?? {};
    const text = JSON.stringify(payload, null, 2) || '{}';

    useEffect(() => {
        if (ref.current) {
            ref.current.removeAttribute('data-highlighted');
            hljs.highlightElement(ref.current);
        }
    }, [text]);

    return (
        <pre className="max-h-[32rem] overflow-auto rounded-xl border border-slate-200 bg-slate-50 p-4 text-[13px] leading-relaxed text-slate-800">
            <code ref={ref} className="language-json !bg-transparent">{text}</code>
        </pre>
    );
}
