import { Link } from 'react-router-dom';
import { useMemo, useState } from 'react';
import { Plus, Trash2 } from 'lucide-react';
import { Button } from '../ui/Button';
import { Input } from '../ui/Input';
import { NativeSelect } from '../ui/NativeSelect';
import { JsonHighlight } from '../ui/JsonHighlight';
import { KeyValueEditor } from './KeyValueEditor';
import { useI18n } from '../../lib/i18n';
import {
    HTTP_METHODS,
    INPUT_TYPES,
    emptyInput,
    serializeDraft,
    syncInputs,
    type HttpToolDraft,
} from '../../lib/httpToolBuilder';

type Connection = { slug: string; name: string; type: string };
type Tab = 'query' | 'headers' | 'body' | 'inputs' | 'auth';

type Props = {
    draft: HttpToolDraft;
    connections: Connection[];
    onChange: (draft: HttpToolDraft) => void;
};

export function HttpToolBuilder({ draft, connections, onChange }: Props) {
    const { t } = useI18n();
    const [tab, setTab] = useState<Tab>('inputs');
    const preview = useMemo(() => serializeDraft(draft), [draft]);

    const set = (patch: Partial<HttpToolDraft>) => {
        const next = { ...draft, ...patch };
        onChange(patch.url !== undefined || patch.query !== undefined || patch.headers !== undefined || patch.body !== undefined ? syncInputs(next) : next);
    };

    const filled = (rows: { key: string; value: string }[]) => rows.filter((row) => row.key.trim() !== '' || row.value.trim() !== '').length;

    const tabs: { id: Tab; label: string; count?: number }[] = [
        { id: 'inputs', label: t('tools.builder.tab_inputs'), count: draft.inputs.filter((row) => row.name.trim() !== '').length },
        { id: 'query', label: t('tools.builder.tab_query'), count: filled(draft.query) },
        { id: 'headers', label: t('tools.builder.tab_headers'), count: filled(draft.headers) },
        { id: 'body', label: t('tools.builder.tab_body'), count: filled(draft.body) },
        { id: 'auth', label: t('tools.builder.tab_auth') },
    ];

    return (
        <div className="space-y-5">
            <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
                <div className="sm:w-28">
                    <NativeSelect
                        value={draft.method}
                        onValueChange={(method) => set({ method })}
                        options={HTTP_METHODS.map((value) => ({ value, label: value }))}
                    />
                </div>
                <Input
                    className="font-mono text-[13px]"
                    value={draft.url}
                    onChange={(e) => set({ url: e.target.value })}
                    placeholder="https://api.example.com/orders/{reference}"
                />
                <div className="sm:w-64">
                    <NativeSelect
                        value={draft.connection === '' ? '__none__' : draft.connection}
                        onValueChange={(value) => set({ connection: value === '__none__' ? '' : value })}
                        options={[
                            { value: '__none__', label: t('tools.connection_none') },
                            ...connections.map((item) => ({
                                value: item.slug,
                                label: `${item.name} · ${item.type}`,
                            })),
                        ]}
                    />
                </div>
            </div>
            <p className="-mt-2 text-[13px] text-slate-500">{t('tools.builder.url_hint')}</p>

            <div className="flex flex-wrap gap-1 border-b border-slate-100 pb-px">
                {tabs.map((item) => (
                    <button
                        key={item.id}
                        type="button"
                        onClick={() => setTab(item.id)}
                        className={`relative -mb-px px-3 py-2.5 text-[13px] font-medium transition ${
                            tab === item.id ? 'text-slate-950' : 'text-slate-400 hover:text-slate-700'
                        }`}
                    >
                        {item.label}
                        {item.count ? <span className="ms-1.5 text-[11px] font-semibold text-slate-500">{item.count}</span> : null}
                        {tab === item.id && <span className="absolute inset-x-2 bottom-0 h-px bg-slate-950" />}
                    </button>
                ))}
            </div>

            <div className="min-h-[180px]">
                {tab === 'query' && (
                    <KeyValueEditor
                        rows={draft.query}
                        onChange={(query) => set({ query })}
                        keyPlaceholder={t('tools.builder.key')}
                        valuePlaceholder={t('tools.builder.value_placeholder')}
                        addLabel={t('tools.builder.add_row')}
                    />
                )}
                {tab === 'headers' && (
                    <KeyValueEditor
                        rows={draft.headers}
                        onChange={(headers) => set({ headers })}
                        keyPlaceholder={t('tools.builder.header')}
                        valuePlaceholder={t('tools.builder.value_placeholder')}
                        addLabel={t('tools.builder.add_row')}
                    />
                )}
                {tab === 'body' && (
                    <div className="space-y-3">
                        <p className="text-xs text-slate-500">{t('tools.builder.body_hint')}</p>
                        <KeyValueEditor
                            rows={draft.body}
                            onChange={(body) => set({ body })}
                            keyPlaceholder={t('tools.builder.body_key')}
                            valuePlaceholder={t('tools.builder.value_placeholder')}
                            addLabel={t('tools.builder.add_row')}
                        />
                    </div>
                )}
                {tab === 'inputs' && (
                    <div className="space-y-3">
                        <p className="text-xs text-slate-500">{t('tools.builder.inputs_hint')}</p>
                        <div className="hidden grid-cols-[1.2fr_7rem_5rem_1fr_auto] gap-2 text-xs font-semibold uppercase tracking-wide text-slate-400 sm:grid">
                            <span>{t('tools.builder.input_name')}</span>
                            <span>{t('tools.builder.input_type')}</span>
                            <span>{t('tools.builder.input_required')}</span>
                            <span>{t('tools.builder.input_help')}</span>
                            <span />
                        </div>
                        {draft.inputs.map((row) => (
                            <div key={row.id} className="grid gap-2 sm:grid-cols-[1.2fr_7rem_5rem_1fr_auto]">
                                <Input
                                    value={row.name}
                                    onChange={(e) =>
                                        onChange({
                                            ...draft,
                                            inputs: draft.inputs.map((item) => (item.id === row.id ? { ...item, name: e.target.value } : item)),
                                        })
                                    }
                                    placeholder="reference"
                                />
                                <NativeSelect
                                    value={row.type}
                                    onValueChange={(type) =>
                                        onChange({
                                            ...draft,
                                            inputs: draft.inputs.map((item) => (item.id === row.id ? { ...item, type } : item)),
                                        })
                                    }
                                    options={INPUT_TYPES.map((value) => ({ value, label: value }))}
                                />
                                <label className="flex h-11 items-center gap-2 text-sm text-slate-600">
                                    <input
                                        type="checkbox"
                                        checked={row.required}
                                        onChange={(e) =>
                                            onChange({
                                                ...draft,
                                                inputs: draft.inputs.map((item) => (item.id === row.id ? { ...item, required: e.target.checked } : item)),
                                            })
                                        }
                                    />
                                    {t('tools.builder.required')}
                                </label>
                                <Input
                                    value={row.description}
                                    onChange={(e) =>
                                        onChange({
                                            ...draft,
                                            inputs: draft.inputs.map((item) => (item.id === row.id ? { ...item, description: e.target.value } : item)),
                                        })
                                    }
                                    placeholder={t('tools.builder.input_help')}
                                />
                                <Button
                                    type="button"
                                    variant="ghost"
                                    className="px-2 text-slate-400 hover:text-red-600"
                                    onClick={() =>
                                        onChange({
                                            ...draft,
                                            inputs: draft.inputs.length === 1 ? [emptyInput()] : draft.inputs.filter((item) => item.id !== row.id),
                                        })
                                    }
                                >
                                    <Trash2 className="h-4 w-4" />
                                </Button>
                            </div>
                        ))}
                        <Button type="button" variant="secondary" onClick={() => onChange({ ...draft, inputs: [...draft.inputs, emptyInput()] })}>
                            <Plus className="h-4 w-4" />
                            {t('tools.builder.add_input')}
                        </Button>
                    </div>
                )}
                {tab === 'auth' && (
                    <div className="space-y-4">
                        <div>
                            <p className="mb-1.5 text-sm font-semibold text-slate-700">{t('tools.fields.connection')}</p>
                            <NativeSelect
                                value={draft.connection === '' ? '__none__' : draft.connection}
                                onValueChange={(value) => set({ connection: value === '__none__' ? '' : value })}
                                options={[
                                    { value: '__none__', label: t('tools.connection_none') },
                                    ...connections.map((item) => ({
                                        value: item.slug,
                                        label: `${item.name} · ${item.type}`,
                                    })),
                                ]}
                            />
                            <p className="mt-1.5 text-xs text-slate-500">
                                {t('tools.connection_hint')}{' '}
                                <Link to="/connections" className="font-semibold text-brand-700 underline">
                                    {t('nav.connections')}
                                </Link>
                            </p>
                        </div>
                        <div className="max-w-xs">
                            <p className="mb-1.5 text-sm font-semibold text-slate-700">{t('tools.builder.timeout')}</p>
                            <Input
                                type="number"
                                min={1}
                                max={120}
                                value={String(draft.timeout)}
                                onChange={(e) => set({ timeout: Number(e.target.value) || 20 })}
                            />
                        </div>
                    </div>
                )}
            </div>
            <div className="rounded-2xl bg-slate-50/80 px-4 py-3">
                <p className="mb-1 text-[10px] font-semibold uppercase tracking-[0.12em] text-slate-400">{t('tools.builder.preview')}</p>
                <p className="mb-2 font-mono text-xs font-semibold text-slate-800">
                    {draft.method} {draft.url}
                </p>
                <JsonHighlight value={preview} />
            </div>
        </div>
    );
}
