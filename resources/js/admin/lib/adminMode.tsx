import { createContext, useCallback, useContext, useMemo, useState, type ReactNode } from 'react';

const STORAGE_KEY = 'agentic_admin_expert_mode';

type AdminModeContextValue = {
    expertMode: boolean;
    setExpertMode: (value: boolean) => void;
    toggleExpertMode: () => void;
};

const AdminModeContext = createContext<AdminModeContextValue | null>(null);

function readExpertMode(): boolean {
    try {
        return localStorage.getItem(STORAGE_KEY) === '1';
    } catch {
        return false;
    }
}

export function AdminModeProvider({ children }: { children: ReactNode }) {
    const [expertMode, setExpertModeState] = useState(readExpertMode);

    const setExpertMode = useCallback((value: boolean) => {
        setExpertModeState(value);
        try {
            localStorage.setItem(STORAGE_KEY, value ? '1' : '0');
        } catch {
            /* ignore */
        }
    }, []);

    const toggleExpertMode = useCallback(() => setExpertMode(!expertMode), [expertMode, setExpertMode]);

    const value = useMemo(
        () => ({ expertMode, setExpertMode, toggleExpertMode }),
        [expertMode, setExpertMode, toggleExpertMode],
    );

    return <AdminModeContext.Provider value={value}>{children}</AdminModeContext.Provider>;
}

export function useAdminMode() {
    const ctx = useContext(AdminModeContext);
    if (!ctx) {
        throw new Error('useAdminMode outside provider');
    }
    return ctx;
}
