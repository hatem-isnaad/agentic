import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';
import { Link } from 'react-router-dom';

type Props = {
    to: string;
    external?: boolean;
    icon: LucideIcon;
    title: string;
    description: string;
    children?: ReactNode;
};

export function DocQuickAction({ to, external, icon: Icon, title, description, children }: Props) {
    const className =
        'group flex h-full flex-col rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-brand-300 hover:shadow-md';

    const inner = (
        <>
            <div className="mb-3 flex h-11 w-11 items-center justify-center rounded-xl bg-gradient-to-br from-brand-500/15 to-violet-500/15 text-brand-700">
                <Icon className="h-5 w-5" strokeWidth={1.75} />
            </div>
            <p className="font-semibold text-slate-900 group-hover:text-brand-800">{title}</p>
            <p className="mt-1 flex-1 text-sm text-slate-600">{description}</p>
            {children}
        </>
    );

    if (external) {
        return (
            <a href={to} target="_blank" rel="noreferrer" className={className}>
                {inner}
            </a>
        );
    }

    return (
        <Link to={to} className={className}>
            {inner}
        </Link>
    );
}
