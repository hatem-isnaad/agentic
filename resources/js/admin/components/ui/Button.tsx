import { clsx } from 'clsx';
import type { ButtonHTMLAttributes } from 'react';

type Variant = 'primary' | 'secondary' | 'ghost' | 'danger';

const variants: Record<Variant, string> = {
    primary:
        'bg-gradient-to-r from-brand-600 to-brand-500 text-white shadow-md shadow-brand-500/25 hover:from-brand-700 hover:to-brand-600 hover:shadow-lg hover:shadow-brand-500/30',
    secondary: 'bg-white text-slate-800 border border-slate-200/90 shadow-sm hover:bg-slate-50 hover:border-slate-300',
    ghost: 'text-slate-600 hover:bg-slate-100/80',
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
                'inline-flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold transition-all duration-200 focus:outline-none focus-visible:ring-4 focus-visible:ring-brand-500/25 disabled:pointer-events-none disabled:opacity-50',
                variants[variant],
                className,
            )}
            {...props}
        />
    );
}
