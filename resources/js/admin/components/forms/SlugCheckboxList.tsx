import { useEffect, useState } from 'react';
import { adminApi, type Paginated } from '../../lib/api';
import { useAdminConfig } from '../../lib/config';
import { useI18n } from '../../lib/i18n';
import { Skeleton } from '../ui/Skeleton';

type Row = { slug: string; name?: string };

type Props = {
    apiPath: string;
    value: string[];
    onChange: (slugs: string[]) => void;
    emptyHint: string;
};

export function SlugCheckboxList({ apiPath, value, onChange, emptyHint }: Props) {
    const boot = useAdminConfig();
    const { t } = useI18n();
    const [items, setItems] = useState<Row[]>([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        adminApi
            .get<Paginated<Row>>(boot, `${apiPath}?per_page=100`)
            .then((res) => setItems(res.data))
            .catch(() => setItems([]))
            .finally(() => setLoading(false));
    }, [boot, apiPath]);

    const toggle = (slug: string) => {
        if (value.includes(slug)) {
            onChange(value.filter((s) => s !== slug));
        } else {
            onChange([...value, slug]);
        }
    };

    if (loading) return <Skeleton className="h-24 w-full" />;

    if (items.length === 0) {
        return <p className="text-sm text-slate-500">{emptyHint}</p>;
    }

    return (
        <div className="max-h-48 space-y-2 overflow-y-auto rounded-xl border border-slate-200 bg-white p-3">
            {items.map((item) => (
                <label key={item.slug} className="flex cursor-pointer items-start gap-3 rounded-lg px-2 py-1.5 hover:bg-slate-50">
                    <input
                        type="checkbox"
                        className="mt-1 rounded border-slate-300"
                        checked={value.includes(item.slug)}
                        onChange={() => toggle(item.slug)}
                    />
                    <span className="text-sm">
                        <span className="font-semibold text-slate-900">{item.name ?? item.slug}</span>
                        <span className="block font-mono text-xs text-slate-500">{item.slug}</span>
                    </span>
                </label>
            ))}
            <p className="border-t border-slate-100 pt-2 text-xs text-slate-500">{t('forms.picker_selected', { count: String(value.length) })}</p>
        </div>
    );
}
