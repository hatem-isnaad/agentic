import { clsx } from 'clsx';
import type { HTMLAttributes } from 'react';

export function Card({ className, ...props }: HTMLAttributes<HTMLDivElement>) {
    return (
        <div
            className={clsx(
                'rounded-2xl border border-slate-200/70 bg-white shadow-[0_0_0_1px_rgba(15,23,42,0.03),0_16px_40px_-28px_rgba(15,23,42,0.35)]',
                className,
            )}
            {...props}
        />
    );
}

export function CardBody({ className, ...props }: HTMLAttributes<HTMLDivElement>) {
    return <div className={clsx('p-5 sm:p-6', className)} {...props} />;
}
