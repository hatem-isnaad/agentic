import { clsx } from 'clsx';
import type { InputHTMLAttributes } from 'react';

export function Input({ className, ...props }: InputHTMLAttributes<HTMLInputElement>) {
    return (
        <input
            className={clsx(
                'h-11 w-full rounded-xl border border-slate-200/90 bg-white px-3.5 text-sm text-slate-900 transition placeholder:text-slate-400 hover:border-slate-300 focus:border-slate-900 focus:outline-none focus:ring-4 focus:ring-slate-900/8',
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
                'w-full min-h-[120px] rounded-xl border border-slate-200/90 bg-white px-3.5 py-3 text-sm text-slate-900 transition placeholder:text-slate-400 hover:border-slate-300 focus:border-slate-900 focus:outline-none focus:ring-4 focus:ring-slate-900/8',
                className,
            )}
            {...props}
        />
    );
}

export function Label({ children, className }: { children: React.ReactNode; className?: string }) {
    return <label className={clsx('mb-1.5 block text-[13px] font-medium text-slate-600', className)}>{children}</label>;
}
