import { Info } from 'lucide-react';
import type { ReactNode } from 'react';

export function HelpCallout({ children }: { children: ReactNode }) {
    return (
        <div className="flex gap-3 rounded-xl border border-brand-200/80 bg-brand-50/80 px-4 py-3 text-sm leading-relaxed text-slate-700">
            <Info className="mt-0.5 h-4 w-4 shrink-0 text-brand-600" />
            <div>{children}</div>
        </div>
    );
}
