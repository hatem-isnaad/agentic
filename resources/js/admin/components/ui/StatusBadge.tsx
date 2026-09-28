import { clsx } from 'clsx';

const styles: Record<string, string> = {
    draft: 'bg-slate-100 text-slate-700 ring-slate-200/80',
    published: 'bg-emerald-50 text-emerald-800 ring-emerald-200/80',
    archived: 'bg-amber-50 text-amber-900 ring-amber-200/80',
    pending: 'bg-sky-50 text-sky-800 ring-sky-200/80',
    running: 'bg-blue-50 text-blue-800 ring-blue-200/80',
    completed: 'bg-emerald-50 text-emerald-800 ring-emerald-200/80',
    failed: 'bg-red-50 text-red-800 ring-red-200/80',
    awaiting_approval: 'bg-violet-50 text-violet-800 ring-violet-200/80',
};

export function StatusBadge({ status, label }: { status: string; label: string }) {
    const key = status?.toLowerCase().replace(/\s+/g, '_') ?? 'draft';
    return (
        <span
            className={clsx(
                'inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-[11px] font-semibold ring-1 ring-inset',
                styles[key] ?? styles.draft,
            )}
        >
            <span className="h-1.5 w-1.5 rounded-full bg-current opacity-70" />
            {label}
        </span>
    );
}
