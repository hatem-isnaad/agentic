import { Plus, Trash2 } from 'lucide-react';
import { Button } from '../ui/Button';
import { Input } from '../ui/Input';
import type { KvRow } from '../../lib/httpToolBuilder';
import { emptyKv } from '../../lib/httpToolBuilder';

type Props = {
    rows: KvRow[];
    onChange: (rows: KvRow[]) => void;
    keyPlaceholder: string;
    valuePlaceholder: string;
    addLabel: string;
};

export function KeyValueEditor({ rows, onChange, keyPlaceholder, valuePlaceholder, addLabel }: Props) {
    const update = (id: string, patch: Partial<KvRow>) => {
        onChange(rows.map((row) => (row.id === id ? { ...row, ...patch } : row)));
    };

    return (
        <div className="space-y-2">
            <div className="grid grid-cols-[1fr_1fr_auto] gap-2 text-xs font-semibold uppercase tracking-wide text-slate-400">
                <span>{keyPlaceholder}</span>
                <span>{valuePlaceholder}</span>
                <span className="w-10" />
            </div>
            {rows.map((row) => (
                <div key={row.id} className="grid grid-cols-[1fr_1fr_auto] gap-2">
                    <Input
                        value={row.key}
                        onChange={(e) => update(row.id, { key: e.target.value })}
                        placeholder={keyPlaceholder}
                        autoComplete="off"
                    />
                    <Input
                        value={row.value}
                        onChange={(e) => update(row.id, { value: e.target.value })}
                        placeholder={valuePlaceholder}
                        autoComplete="off"
                    />
                    <Button
                        type="button"
                        variant="ghost"
                        className="px-2 text-slate-400 hover:text-red-600"
                        onClick={() => onChange(rows.length === 1 ? [emptyKv()] : rows.filter((item) => item.id !== row.id))}
                        aria-label="Remove"
                    >
                        <Trash2 className="h-4 w-4" />
                    </Button>
                </div>
            ))}
            <Button type="button" variant="secondary" onClick={() => onChange([...rows, emptyKv()])}>
                <Plus className="h-4 w-4" />
                {addLabel}
            </Button>
        </div>
    );
}
