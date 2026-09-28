import { Check, Copy } from 'lucide-react';
import { useMemo, useState } from 'react';
import { useI18n } from '../../lib/i18n';
import { highlightDocCode, inferDocCodeLanguage } from './doc-highlight';

import 'highlight.js/styles/github-dark.min.css';

type Props = {
    code: string;
    title?: string;
    /** light = prose-style (Laravel inline blocks); dark = terminal (default) */
    variant?: 'dark' | 'light';
};

export function CodeBlock({ code, title, variant = 'dark' }: Props) {
    const { t } = useI18n();
    const [copied, setCopied] = useState(false);
    const language = inferDocCodeLanguage(code, title);
    const html = useMemo(() => highlightDocCode(code, language), [code, language]);

    const copy = async () => {
        await navigator.clipboard.writeText(code);
        setCopied(true);
        window.setTimeout(() => setCopied(false), 2000);
    };

    const isDark = variant === 'dark';

    return (
        <figure dir="ltr" className={`docs-code-block group ${isDark ? 'docs-code-block--dark' : 'docs-code-block--light'}`}>
            <div className="docs-code-block__toolbar">
                <div className="flex min-w-0 items-center gap-2">
                    {isDark ? (
                        <span className="docs-code-block__dots" aria-hidden>
                            <span />
                            <span />
                            <span />
                        </span>
                    ) : null}
                    <span className="docs-code-block__label">{title ?? language}</span>
                    <span className="docs-code-block__lang">{language}</span>
                </div>
                <button
                    type="button"
                    className="docs-code-block__copy"
                    onClick={copy}
                    aria-label={t('docs.copy_code')}
                >
                    {copied ? <Check className="h-3.5 w-3.5" /> : <Copy className="h-3.5 w-3.5" />}
                    <span>{copied ? t('docs.copied_code') : t('docs.copy_code')}</span>
                </button>
            </div>
            <pre className="docs-code-block__pre">
                <code
                    className={`hljs language-${language}`}
                    // Highlight output is generated locally from static doc strings (no user HTML).
                    dangerouslySetInnerHTML={{ __html: html }}
                />
            </pre>
        </figure>
    );
}
