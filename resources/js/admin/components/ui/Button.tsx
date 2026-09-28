import { clsx } from 'clsx';
import type { ButtonHTMLAttributes } from 'react';

type Variant = 'primary' | 'secondary' | 'ghost' | 'danger';

const variants: Record<Variant, string> = {
    primary: 'bg-slate-950 text-white shadow-[0_1px_0_rgba(255,255,255,0.12)_inset,0_8px_16px_-8px_rgba(15,23,42,0.55)] hover:bg-slate-800 focus-visible:ring-slate-900/20',
    secondary: 'bg-white text-slate-800 border border-slate-200 hover:bg-slate-50 hover:border-slate-300',
    ghost: 'text-slate-500 hover:bg-white hover:text-slate-900',
    danger: 'bg-red-50 text-red-700 border border-red-200/80 hover:bg-red-100',
};

export function Button({
    variant = 'primary',
    className,
    ...props
}: ButtonHTMLAttributes<HTMLButtonElement> & { variant?: Variant }) {
    return (
        <button
            className={clsx(
                'inline-flex h-10 items-center justify-center gap-2 rounded-xl px-4 text-sm font-semibold tracking-tight transition focus:outline-none focus-visible:ring-4 disabled:pointer-events-none disabled:opacity-40',
                variants[variant],
                className,
            )}
            {...props}
        />
    );
}
