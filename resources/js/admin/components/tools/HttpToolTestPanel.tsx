import { Play } from 'lucide-react';
import { useMemo, useState } from 'react';
import { adminApi } from '../../lib/api';
import { useAdminConfig } from '../../lib/config';
import { useI18n } from '../../lib/i18n';
import { Button } from '../ui/Button';
import { Card, CardBody } from '../ui/Card';
import { FormField } from '../ui/FormField';
import { Input } from '../ui/Input';
import { JsonHighlight } from '../ui/JsonHighlight';

type Field = { name: string; type: string; required: boolean; description: string };

type TestResult = {
    success: boolean;
    data: unknown;
    error: string | null;
    duration_ms: number;
    request: {
        method?: string;
        url?: string;
        query?: Record<string, unknown>;
        headers?: Record<string, unknown>;
        body?: unknown;
    } | null;
};

function fieldsFromSchema(schema: Record<string, unknown> | undefined): Field[] {
    if (!schema) return [];
    const properties = (schema.properties && typeof schema.properties === 'object' ? schema.properties : schema) as Record<
        string,
        unknown
    >;
    const required = Array.isArray(schema.required) ? schema.required.map(String) : [];
    const skip = new Set(['type', 'properties', 'required']);
    const fields: Field[] = [];

    for (const [name, definition] of Object.entries(properties)) {
        if (skip.has(name)) continue;
        const meta = definition && typeof definition === 'object' ? (definition as Record<string, unknown>) : {};
        fields.push({
            name,
            type: typeof meta.type === 'string' ? meta.type : 'string',
            required: required.includes(name) || meta.required === true,
            description: typeof meta.description === 'string' ? meta.description : '',
        });
    }

    return fields;
}

function castValue(raw: string, type: string): unknown {
    if (type === 'integer' || type === 'int') return Number.parseInt(raw, 10);
    if (type === 'number') return Number.parseFloat(raw);
    if (type === 'boolean' || type === 'bool') return raw === 'true' || raw === '1';
    return raw;
}

type Props = {
    slug: string;
    inputSchema?: Record<string, unknown>;
};

export function HttpToolTestPanel({ slug, inputSchema }: Props) {
    const boot = useAdminConfig();
    const { t } = useI18n();
    const fields = useMemo(() => fieldsFromSchema(inputSchema), [inputSchema]);
    const [values, setValues] = useState<Record<string, string>>({});
    const [running, setRunning] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [result, setResult] = useState<TestResult | null>(null);

    const run = async () => {
        setRunning(true);
        setError(null);
        const argumentsPayload: Record<string, unknown> = {};
        for (const field of fields) {
            const raw = (values[field.name] ?? '').trim();
            if (raw === '') {
                if (field.required) {
                    setError(t('tools.test.missing', { name: field.name }));
                    setRunning(false);
                    return;
                }
                continue;
            }
            argumentsPayload[field.name] = castValue(raw, field.type);
        }

        try {
            const res = await adminApi.post<{ data: TestResult }>(boot, `/tools/${slug}/test`, {
                arguments: argumentsPayload,
            });
            setResult(res.data);
        } catch (e) {
            setResult(null);
            setError(e instanceof Error ? e.message : t('tools.test.failed'));
        } finally {
            setRunning(false);
        }
    };

    return (
        <Card>
            <CardBody className="space-y-5">
                <div>
                    <h2 className="text-sm font-semibold text-slate-900">{t('tools.test.title')}</h2>
                    <p className="mt-1 text-sm text-slate-500">{t('tools.test.intro')}</p>
                </div>

                {fields.length === 0 ? (
                    <p className="text-sm text-slate-500">{t('tools.test.no_inputs')}</p>
                ) : (
                    <div className="grid gap-4 sm:grid-cols-2">
                        {fields.map((field) => (
                            <FormField
                                key={field.name}
                                label={field.name}
                                required={field.required}
                                hint={field.description || undefined}
                            >
                                <Input
                                    value={values[field.name] ?? ''}
                                    onChange={(event) => setValues((prev) => ({ ...prev, [field.name]: event.target.value }))}
                                    placeholder={field.type}
                                />
                            </FormField>
                        ))}
                    </div>
                )}

                {error && <p className="text-sm font-medium text-red-600">{error}</p>}

                <Button type="button" onClick={() => void run()} disabled={running}>
                    <Play className="h-4 w-4" />
                    {running ? t('tools.test.running') : t('tools.test.run')}
                </Button>

                {result && (
                    <div className="space-y-4">
                        <div className="flex flex-wrap items-center gap-3 text-sm">
                            <span
                                className={
                                    result.success
                                        ? 'rounded-full bg-emerald-50 px-2.5 py-1 font-semibold text-emerald-700'
                                        : 'rounded-full bg-red-50 px-2.5 py-1 font-semibold text-red-700'
                                }
                            >
                                {result.success ? t('tools.test.ok') : t('tools.test.fail')}
                            </span>
                            <span className="text-slate-500">{result.duration_ms} ms</span>
                            {result.request?.method && result.request.url && (
                                <span className="font-mono text-xs text-slate-600">
                                    {result.request.method} {result.request.url}
                                </span>
                            )}
                        </div>
                        {result.error && <p className="text-sm text-red-700">{result.error}</p>}
                        <JsonHighlight data={result.success ? result.data : { error: result.error, request: result.request }} />
                    </div>
                )}
            </CardBody>
        </Card>
    );
}
