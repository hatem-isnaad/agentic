import { Info } from 'lucide-react';
import type { ReactNode } from 'react';

export function HelpCallout({ children }: { children: ReactNode }) {
    return (
        <div className="mb-5 flex gap-2.5 text-[13px] leading-relaxed text-slate-500">
            <Info className="mt-0.5 h-4 w-4 shrink-0 text-slate-400" />
            <div>{children}</div>
        </div>
    );
}
