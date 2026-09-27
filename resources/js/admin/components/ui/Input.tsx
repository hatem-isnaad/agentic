import { clsx } from 'clsx';
import type { InputHTMLAttributes } from 'react';

export function Input({ className, ...props }: InputHTMLAttributes<HTMLInputElement>) {
    return (
        <input
            className={clsx(
                'w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm shadow-sm transition placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20',
                className,
            )}
            {...props}
        />
    );
}

export function Textarea({ className, ...props }: React.TextareaHTMLAttributes<HTMLTextAreaElement>) {
    return (
        <textarea
            className={clsx(
                'w-full min-h-[120px] rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm shadow-sm transition placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20',
                className,
            )}
            {...props}
        />
    );
}

export function Label({ children, className }: { children: React.ReactNode; className?: string }) {
    return <label className={clsx('mb-1.5 block text-sm font-semibold text-slate-700', className)}>{children}</label>;
}
