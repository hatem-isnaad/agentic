import { clsx } from 'clsx';
import type { HTMLAttributes, ReactNode, TdHTMLAttributes, ThHTMLAttributes } from 'react';
import { Card } from './Card';

export function DataTable({
    toolbar,
    children,
    className,
}: {
    toolbar?: ReactNode;
    children: ReactNode;
    className?: string;
}) {
    return (
        <Card className={clsx('overflow-hidden', className)}>
            {toolbar && <div className="border-b border-slate-100 px-4 py-3 sm:px-5">{toolbar}</div>}
            <div className="overflow-x-auto">{children}</div>
        </Card>
    );
}

export function Table({ className, ...props }: HTMLAttributes<HTMLTableElement>) {
    return <table className={clsx('min-w-full text-sm', className)} {...props} />;
}

export function THead({ children }: { children: ReactNode }) {
    return (
        <thead>
            <tr className="border-b border-slate-100 bg-slate-50/80 text-start text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-400">
                {children}
            </tr>
        </thead>
    );
}

export function Th({ className, ...props }: ThHTMLAttributes<HTMLTableCellElement>) {
    return <th className={clsx('px-5 py-3 font-semibold', className)} {...props} />;
}

export function Td({ className, ...props }: TdHTMLAttributes<HTMLTableCellElement>) {
    return <td className={clsx('px-5 py-3.5 text-slate-700', className)} {...props} />;
}

export function Tr({ className, ...props }: HTMLAttributes<HTMLTableRowElement>) {
    return <tr className={clsx('border-b border-slate-100 last:border-0 transition-colors hover:bg-slate-50/90', className)} {...props} />;
}

export function ErrorBanner({ message }: { message: string }) {
    return (
        <div className="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
            {message}
        </div>
    );
}
