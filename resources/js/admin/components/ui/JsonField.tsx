import { Textarea } from './Input';

type Props = {
    value: string;
    onChange: (value: string) => void;
    rows?: number;
    placeholder?: string;
};

export function parseJsonObject(raw: string, label: string): Record<string, unknown> {
    const trimmed = raw.trim();
    if (trimmed === '') {
        return {};
    }
    const parsed = JSON.parse(trimmed) as unknown;
    if (typeof parsed !== 'object' || parsed === null || Array.isArray(parsed)) {
        throw new Error(`${label} must be a JSON object.`);
    }
    return parsed as Record<string, unknown>;
}

export function JsonField({ value, onChange, rows = 10, placeholder }: Props) {
    return (
        <Textarea
            dir="ltr"
            value={value}
            onChange={(e) => onChange(e.target.value)}
            rows={rows}
            className="ag-json font-mono text-left text-xs leading-relaxed"
            placeholder={placeholder}
            spellCheck={false}
        />
    );
}
