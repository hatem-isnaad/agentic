import type { ReactNode } from 'react';
import { Label } from './Input';

type Props = {
    label: string;
    hint?: string;
    children: ReactNode;
    required?: boolean;
};

export function FormField({ label, hint, children, required }: Props) {
    return (
        <div className="ag-field space-y-1.5">
            <Label>
                {label}
                {required && <span className="text-red-500"> *</span>}
            </Label>
            {children}
            {hint && <p className="text-xs text-slate-500">{hint}</p>}
        </div>
    );
}
