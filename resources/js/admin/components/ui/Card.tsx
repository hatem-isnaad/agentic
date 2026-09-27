import { clsx } from 'clsx';
import type { HTMLAttributes } from 'react';

export function Card({ className, ...props }: HTMLAttributes<HTMLDivElement>) {
    return (
        <div
            className={clsx(
                'rounded-2xl border border-slate-200/70 bg-white/90 shadow-[var(--shadow-soft)] backdrop-blur-sm',
                className,
            )}
            {...props}
        />
    );
}

export function CardBody({ className, ...props }: HTMLAttributes<HTMLDivElement>) {
    return <div className={clsx('p-6', className)} {...props} />;
}
