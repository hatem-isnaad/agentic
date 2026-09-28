import type { ReactNode } from 'react';

/** Full-width content area (dashboard-style). */
export function PageContainer({ children }: { children: ReactNode }) {
    return <div className="w-full">{children}</div>;
}

/** Full main column after the sidebar. */
export function FormContainer({ children }: { children: ReactNode }) {
    return <div className="w-full">{children}</div>;
}
