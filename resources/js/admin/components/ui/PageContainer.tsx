import type { ReactNode } from 'react';

/** Full-width content area (dashboard-style). */
export function PageContainer({ children }: { children: ReactNode }) {
    return <div className="w-full">{children}</div>;
}

/** Centered form column within full-width layout. */
export function FormContainer({ children }: { children: ReactNode }) {
    return <div className="mx-auto w-full max-w-3xl">{children}</div>;
}
