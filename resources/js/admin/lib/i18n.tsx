import { createContext, useCallback, useContext, useEffect, useMemo, useState, type ReactNode } from 'react';
import { adminApi } from './api';
import { useAdminConfig } from './config';

type Tree = Record<string, unknown>;

type I18nContextValue = {
    t: (key: string, replace?: Record<string, string>) => string;
    locale: string;
    setLocale: (locale: string) => Promise<void>;
    direction: 'ltr' | 'rtl';
    ready: boolean;
};

const I18nContext = createContext<I18nContextValue | null>(null);

function resolve(tree: Tree, key: string): string | undefined {
    const parts = key.split('.');
    let cur: unknown = tree;
    for (const part of parts) {
        if (cur === null || typeof cur !== 'object') return undefined;
        cur = (cur as Tree)[part];
    }
    return typeof cur === 'string' ? cur : undefined;
}

export function I18nProvider({ children }: { children: ReactNode }) {
    const boot = useAdminConfig();
    const [tree, setTree] = useState<Tree>({});
    const [fallbackTree, setFallbackTree] = useState<Tree>({});
    const [locale, setLocaleState] = useState(boot.locale);
    const [direction, setDirection] = useState<'ltr' | 'rtl'>(boot.direction);
    const [ready, setReady] = useState(false);

    const load = useCallback(async (loc: string) => {
        const res = await adminApi.get<{ data: Record<string, Tree> }>({ ...boot, locale: loc }, '/translations');
        const en = res.data.en ?? {};
        setFallbackTree(en);
        setTree(loc === 'en' ? en : (res.data[loc] ?? {}));
        setReady(true);
    }, [boot]);

    useEffect(() => {
        load(locale).catch(() => setReady(true));
    }, [load, locale]);

    useEffect(() => {
        document.documentElement.lang = locale;
        document.documentElement.dir = direction;
    }, [locale, direction]);

    const setLocale = async (loc: string) => {
        await adminApi.put({ ...boot, locale: loc }, '/locale', { locale: loc });
        setLocaleState(loc);
        setDirection(boot.rtlLocales.includes(loc) ? 'rtl' : 'ltr');
        await load(loc);
    };

    const t = useCallback(
        (key: string, replace?: Record<string, string>) => {
            let value = resolve(tree, key) ?? resolve(fallbackTree, key) ?? key;
            if (replace) {
                Object.entries(replace).forEach(([k, v]) => {
                    value = value.replace(`:${k}`, v);
                });
            }
            return value;
        },
        [tree, fallbackTree],
    );

    const value = useMemo(
        () => ({ t, locale, setLocale, direction, ready }),
        [t, locale, direction, ready],
    );

    return <I18nContext.Provider value={value}>{children}</I18nContext.Provider>;
}

export function useI18n() {
    const ctx = useContext(I18nContext);
    if (!ctx) throw new Error('useI18n outside provider');
    return ctx;
}
