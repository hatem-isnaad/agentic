import { Info, Lightbulb, Target } from 'lucide-react';
import type { ReactNode } from 'react';

type Variant = 'goal' | 'info' | 'tip';

const styles: Record<Variant, { box: string; icon: string; Icon: typeof Info }> = {
    goal: {
        box: 'border-brand-200/80 bg-gradient-to-br from-brand-50/90 to-white',
        icon: 'text-brand-600',
        Icon: Target,
    },
    info: {
        box: 'border-sky-200/80 bg-sky-50/50',
        icon: 'text-sky-600',
        Icon: Info,
    },
    tip: {
        box: 'border-amber-200/80 bg-amber-50/40',
        icon: 'text-amber-600',
        Icon: Lightbulb,
    },
};

type Props = {
    variant: Variant;
    title?: string;
    children: ReactNode;
};

export function DocCallout({ variant, title, children }: Props) {
    const { box, icon, Icon } = styles[variant];

    return (
        <div className={`docs-callout flex gap-3 rounded-xl border px-4 py-3.5 ${box}`}>
            <Icon className={`mt-0.5 h-5 w-5 shrink-0 ${icon}`} strokeWidth={2} aria-hidden />
            <div className="min-w-0 text-sm leading-relaxed text-slate-700">
                {title ? <p className="mb-1 font-semibold text-slate-900">{title}</p> : null}
                {children}
            </div>
        </div>
    );
}
