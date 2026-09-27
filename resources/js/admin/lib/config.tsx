import { createContext, useContext, type ReactNode } from 'react';

export type AdminBoot = {
    apiPrefix: string;
    webPrefix: string;
    locale: string;
    direction: 'ltr' | 'rtl';
    locales: string[];
    rtlLocales: string[];
};

declare global {
    interface Window {
        __AGENTIC_ADMIN__: AdminBoot;
    }
}

const AdminConfigContext = createContext<AdminBoot>(window.__AGENTIC_ADMIN__);

export function AdminConfigProvider({ children }: { children: ReactNode }) {
    return (
        <AdminConfigContext.Provider value={window.__AGENTIC_ADMIN__}>
            {children}
        </AdminConfigContext.Provider>
    );
}

export function useAdminConfig() {
    return useContext(AdminConfigContext);
}
