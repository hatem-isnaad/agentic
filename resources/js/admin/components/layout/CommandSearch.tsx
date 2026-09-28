import { Search } from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAdminMode } from '../../lib/adminMode';
import { navGroupsForMode } from '../../lib/nav';
import { useI18n } from '../../lib/i18n';

export function CommandSearch() {
    const { t } = useI18n();
    const { expertMode } = useAdminMode();
    const navigate = useNavigate();
    const [q, setQ] = useState('');
    const [open, setOpen] = useState(false);
    const [active, setActive] = useState(0);
    const box = useRef<HTMLDivElement>(null);
    const input = useRef<HTMLInputElement>(null);

    const items = useMemo(() => {
        const all = navGroupsForMode(expertMode).flatMap((group) => group.items);
        const needle = q.trim().toLowerCase();
        if (!needle) return all.slice(0, 8);
        return all.filter((item) => t(item.labelKey).toLowerCase().includes(needle) || item.to.includes(needle)).slice(0, 8);
    }, [expertMode, q, t]);

    useEffect(() => {
        const onKey = (event: KeyboardEvent) => {
            if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
                event.preventDefault();
                input.current?.focus();
                setOpen(true);
            }
            if (event.key === 'Escape') {
                setOpen(false);
                input.current?.blur();
            }
        };
        window.addEventListener('keydown', onKey);
        return () => window.removeEventListener('keydown', onKey);
    }, []);

    useEffect(() => {
        const onClick = (event: MouseEvent) => {
            if (box.current && !box.current.contains(event.target as Node)) {
                setOpen(false);
            }
        };
        document.addEventListener('mousedown', onClick);
        return () => document.removeEventListener('mousedown', onClick);
    }, []);

    const go = (to: string) => {
        navigate(to);
        setQ('');
        setOpen(false);
    };

    return (
        <div ref={box} className="relative w-full max-w-md">
            <Search className="pointer-events-none absolute start-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
            <input
                ref={input}
                value={q}
                onChange={(e) => {
                    setQ(e.target.value);
                    setOpen(true);
                    setActive(0);
                }}
                onFocus={() => setOpen(true)}
                onKeyDown={(event) => {
                    if (event.key === 'ArrowDown') {
                        event.preventDefault();
                        setActive((i) => Math.min(i + 1, items.length - 1));
                    } else if (event.key === 'ArrowUp') {
                        event.preventDefault();
                        setActive((i) => Math.max(i - 1, 0));
                    } else if (event.key === 'Enter' && items[active]) {
                        event.preventDefault();
                        go(items[active].to);
                    }
                }}
                placeholder={t('chrome.search')}
                className="h-10 w-full rounded-xl border border-slate-200/80 bg-slate-50/80 pe-14 ps-9 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-brand-400 focus:bg-white focus:ring-4 focus:ring-brand-500/10"
            />
            <kbd className="pointer-events-none absolute end-2.5 top-1/2 hidden -translate-y-1/2 rounded-md border border-slate-200 bg-white px-1.5 py-0.5 text-[10px] font-semibold text-slate-400 sm:inline">
                ⌘K
            </kbd>
            {open && (
                <div className="absolute inset-x-0 top-[calc(100%+6px)] z-50 overflow-hidden rounded-xl border border-slate-200/90 bg-white py-1 shadow-[var(--shadow-card)]">
                    <p className="px-3 py-1.5 text-[10px] font-semibold uppercase tracking-wider text-slate-400">{t('chrome.search_hint')}</p>
                    {items.length === 0 ? (
                        <p className="px-3 py-4 text-sm text-slate-500">{t('chrome.no_results')}</p>
                    ) : (
                        items.map((item, index) => (
                            <button
                                key={item.to}
                                type="button"
                                onMouseEnter={() => setActive(index)}
                                onClick={() => go(item.to)}
                                className={`flex w-full items-center justify-between px-3 py-2 text-sm ${
                                    index === active ? 'bg-slate-50 text-slate-900' : 'text-slate-600'
                                }`}
                            >
                                <span className="font-medium">{t(item.labelKey)}</span>
                                <span className="font-mono text-[11px] text-slate-400">{item.to}</span>
                            </button>
                        ))
                    )}
                </div>
            )}
        </div>
    );
}
