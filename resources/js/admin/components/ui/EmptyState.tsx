import type { LucideIcon } from 'lucide-react';

export function EmptyState({
    icon: Icon,
    title,
    description,
}: {
    icon: LucideIcon;
    title: string;
    description: string;
}) {
    return (
        <div className="flex flex-col items-center justify-center px-6 py-16 text-center">
            <div className="mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-500/15 to-violet-500/15 text-brand-600">
                <Icon className="h-7 w-7" strokeWidth={1.75} />
            </div>
            <p className="text-lg font-semibold text-slate-800">{title}</p>
            <p className="mt-1 max-w-sm text-sm text-slate-500">{description}</p>
        </div>
    );
}
