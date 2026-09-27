import type { ReactNode } from 'react';

type Props = {
    id: string;
    title: string;
    subtitle?: string;
    step?: number;
    children: ReactNode;
};

export function DocSection({ id, title, subtitle, step, children }: Props) {
    return (
        <section id={id} className="scroll-mt-24 border-b border-slate-200/80 pb-12 last:border-b-0">
            <div className="mb-6 flex flex-wrap items-start gap-3">
                {step !== undefined && (
                    <span
                        className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-brand-600 text-sm font-bold text-white shadow-sm"
                        aria-hidden
                    >
                        {step}
                    </span>
                )}
                <div className="min-w-0 flex-1">
                    <h2 className="text-xl font-bold tracking-tight text-slate-900">{title}</h2>
                    {subtitle ? <p className="mt-1 text-sm leading-relaxed text-slate-600">{subtitle}</p> : null}
                </div>
            </div>
            <div className="space-y-5 text-sm leading-relaxed text-slate-700">{children}</div>
        </section>
    );
}
