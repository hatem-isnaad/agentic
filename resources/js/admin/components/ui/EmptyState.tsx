import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';

export function EmptyState({
    icon: Icon,
    title,
    description,
    action,
}: {
    icon: LucideIcon;
    title: string;
    description: string;
    action?: ReactNode;
}) {
    return (
        <div className="flex flex-col items-center justify-center px-6 py-14 text-center">
            <div className="mb-3 flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-slate-500">
                <Icon className="h-5 w-5" strokeWidth={1.75} />
            </div>
            <p className="text-sm font-semibold text-slate-900">{title}</p>
            <p className="mt-1 max-w-sm text-sm text-slate-500">{description}</p>
            {action && <div className="mt-4">{action}</div>}
        </div>
    );
}
