import { Controller, useForm, useWatch } from 'react-hook-form';
import { useEffect, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { adminApi } from '../lib/api';
import { useAdminConfig } from '../lib/config';
import { useStatusOptions } from '../lib/hooks';
import { useI18n } from '../lib/i18n';
import { slugify } from '../lib/slugify';
import { Button } from '../components/ui/Button';
import { Card, CardBody } from '../components/ui/Card';
import { FormField } from '../components/ui/FormField';
import { Input, Textarea } from '../components/ui/Input';
import { NativeSelect } from '../components/ui/NativeSelect';
import { JsonField, parseJsonObject } from '../components/ui/JsonField';
import { FormSection } from '../components/ui/FormSection';
import { HelpCallout } from '../components/ui/HelpCallout';
import { FormContainer } from '../components/ui/PageContainer';
import { PageHeader } from '../components/ui/PageHeader';
import { Skeleton } from '../components/ui/Skeleton';

type FormValues = {
    name: string;
    slug: string;
    description: string;
    driver: string;
    status: string;
    publish: string;
    http_method: string;
    http_url: string;
    http_param: string;
    definition_json: string;
    config_json: string;
};

const DRIVER_OPTIONS = [
    { value: 'http', label: 'HTTP API' },
    { value: 'mcp', label: 'MCP (sync first)' },
    { value: 'code', label: 'Code (PHP handler)' },
];

const METHOD_OPTIONS = [
    { value: 'GET', label: 'GET' },
    { value: 'POST', label: 'POST' },
    { value: 'PUT', label: 'PUT' },
    { value: 'PATCH', label: 'PATCH' },
    { value: 'DELETE', label: 'DELETE' },
];

function buildHttpDefinition(method: string, url: string, param: string): Record<string, unknown> {
    const def: Record<string, unknown> = { method, url };
    const p = param.trim();
    if (p !== '') {
        def.input_schema = { [p]: { type: 'string', required: true } };
    }
    return def;
}

export function ToolFormPage() {
    const { slug } = useParams();
    const isEdit = Boolean(slug);
    const boot = useAdminConfig();
    const { t } = useI18n();
    const statusOptions = useStatusOptions();
    const navigate = useNavigate();
    const [error, setError] = useState<string | null>(null);
    const [loading, setLoading] = useState(isEdit);
    const [advanced, setAdvanced] = useState(false);
    const [codeHandlers, setCodeHandlers] = useState<{ handler: string; description: string; input_schema: Record<string, unknown> }[]>([]);
    const [selectedHandler, setSelectedHandler] = useState('');

    const { register, handleSubmit, control, reset, setValue } = useForm<FormValues>({
        defaultValues: {
            driver: 'http',
            status: 'draft',
            publish: '1',
            http_method: 'GET',
            http_url: 'https://api.example.com/resource/{id}',
            http_param: 'id',
            definition_json: '{}',
            config_json: '{}',
        },
    });

    const driver = useWatch({ control, name: 'driver' });
    const name = useWatch({ control, name: 'name' });

    useEffect(() => {
        if (!isEdit && name) {
            setValue('slug', slugify(name));
        }
    }, [name, isEdit, setValue]);

    useEffect(() => {
        if (driver !== 'code') return;
        adminApi
            .get<{ data: { handler: string; description: string; input_schema: Record<string, unknown> }[] }>(boot, '/code-handlers')
            .then((res) => setCodeHandlers(res.data))
            .catch(() => setCodeHandlers([]));
    }, [boot, driver]);

    useEffect(() => {
        if (!slug) return;
        adminApi
            .get<{ data: Record<string, unknown> }>(boot, `/tools/${slug}`)
            .then((res) => {
                const d = res.data;
                const def = (d.definition ?? {}) as Record<string, unknown>;
                const schema = def.input_schema as Record<string, unknown> | undefined;
                const firstParam = schema ? Object.keys(schema)[0] ?? 'id' : 'id';
                reset({
                    name: String(d.name ?? ''),
                    slug: String(d.slug ?? slug),
                    description: String(d.description ?? ''),
                    driver: String(d.driver ?? 'http'),
                    status: String(d.status ?? 'draft'),
                    publish: '0',
                    http_method: String(def.method ?? 'GET'),
                    http_url: String(def.url ?? ''),
                    http_param: firstParam,
                    definition_json: JSON.stringify(def, null, 2),
                    config_json: JSON.stringify(d.config ?? {}, null, 2),
                });
            })
            .catch(() => setError('Load failed'))
            .finally(() => setLoading(false));
    }, [boot, slug, reset]);

    const onSubmit = handleSubmit(async (values) => {
        setError(null);
        try {
            let definition: Record<string, unknown>;
            if (values.driver === 'http' && !advanced) {
                definition = buildHttpDefinition(values.http_method, values.http_url, values.http_param);
            } else if (values.driver === 'code' && !advanced && selectedHandler) {
                const meta = codeHandlers.find((h) => h.handler === selectedHandler);
                definition = {
                    handler: selectedHandler,
                    input_schema: meta?.input_schema ?? { type: 'object', properties: {} },
                };
            } else {
                definition = parseJsonObject(values.definition_json, 'Definition');
            }
            const config = parseJsonObject(values.config_json, 'Config');
            const payload = {
                name: values.name,
                slug: values.slug,
                description: values.description || null,
                driver: values.driver,
                status: values.status,
                config,
                definition,
                publish: values.publish === '1',
            };
            if (isEdit && slug) {
                await adminApi.put(boot, `/tools/${slug}`, payload);
                navigate(`/tools/${slug}`);
            } else {
                const res = await adminApi.post<{ data: { slug: string } }>(boot, '/tools', payload);
                navigate(`/tools/${res.data.slug}`);
            }
        } catch (e) {
            setError(e instanceof Error ? e.message : 'Save failed');
        }
    });

    if (loading) {
        return (
            <FormContainer>
                <Skeleton className="h-96 w-full" />
            </FormContainer>
        );
    }

    return (
        <FormContainer>
            <PageHeader title={isEdit ? t('tools.edit_heading', { name: slug ?? '' }) : t('tools.create_heading')} description={t('tools.form_intro_simple')} />
            {error && <div className="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{error}</div>}

            {driver === 'mcp' && (
                <HelpCallout>
                    {t('tools.mcp_hint')}{' '}
                    <Link to="/mcp-servers" className="font-semibold text-brand-700 underline">
                        {t('nav.mcp')}
                    </Link>
                </HelpCallout>
            )}
            {driver === 'code' && (
                <HelpCallout>
                    {t('tools.code_hint')}{' '}
                    <Link to="/custom-code-tools" className="font-semibold text-brand-700 underline">
                        {t('nav.custom_code_tools')}
                    </Link>
                </HelpCallout>
            )}

            <Card>
                <CardBody>
                    <form onSubmit={onSubmit} className="space-y-6">
                        <FormSection title={t('forms.section_basics')} description={t('tools.section_basics_desc')}>
                            <FormField label={t('fields.name')} required>
                                <Input {...register('name', { required: true })} placeholder={t('tools.name_placeholder')} />
                            </FormField>
                            <FormField label={t('fields.slug')} required hint={t('placeholders.slug_auto')}>
                                <Input {...register('slug', { required: true })} readOnly={isEdit} />
                            </FormField>
                            <FormField label={t('fields.driver')} required>
                                <Controller name="driver" control={control} render={({ field }) => <NativeSelect value={field.value} onValueChange={field.onChange} options={DRIVER_OPTIONS} />} />
                            </FormField>
                            <FormField label={t('fields.description')}>
                                <Textarea {...register('description')} rows={2} />
                            </FormField>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <FormField label={t('fields.status')}>
                                    <Controller name="status" control={control} render={({ field }) => <NativeSelect value={field.value} onValueChange={field.onChange} options={statusOptions} />} />
                                </FormField>
                                <FormField label={t('tools.fields.publish')} hint={t('tools.publish_hint')}>
                                    <Controller
                                        name="publish"
                                        control={control}
                                        render={({ field }) => (
                                            <NativeSelect
                                                value={field.value}
                                                onValueChange={field.onChange}
                                                options={[
                                                    { value: '1', label: t('tools.publish_yes') },
                                                    { value: '0', label: t('tools.publish_no') },
                                                ]}
                                            />
                                        )}
                                    />
                                </FormField>
                            </div>
                        </FormSection>

                        {driver === 'code' && !advanced && (
                            <FormSection title={t('custom_tools.pick_handler')} description={t('custom_tools.pick_handler_desc')}>
                                <FormField label={t('custom_tools.col_handler')}>
                                    <NativeSelect
                                        value={selectedHandler}
                                        onValueChange={(v) => {
                                            setSelectedHandler(v);
                                            const meta = codeHandlers.find((h) => h.handler === v);
                                            if (meta && !isEdit) {
                                                setValue('name', meta.description);
                                                setValue('slug', slugify(meta.description));
                                            }
                                        }}
                                        options={[
                                            { value: '', label: t('custom_tools.select_handler') },
                                            ...codeHandlers.map((h) => ({ value: h.handler, label: `${h.handler} — ${h.description}` })),
                                        ]}
                                    />
                                </FormField>
                            </FormSection>
                        )}

                        {driver === 'http' && !advanced && (
                            <FormSection title={t('tools.section_http')} description={t('tools.section_http_desc')}>
                                <FormField label={t('tools.fields.method')}>
                                    <Controller name="http_method" control={control} render={({ field }) => <NativeSelect value={field.value} onValueChange={field.onChange} options={METHOD_OPTIONS} />} />
                                </FormField>
                                <FormField label={t('tools.fields.url')} hint={t('tools.url_hint')}>
                                    <Input {...register('http_url', { required: true })} />
                                </FormField>
                                <FormField label={t('tools.fields.param')} hint={t('tools.param_hint')}>
                                    <Input {...register('http_param')} placeholder="id" />
                                </FormField>
                            </FormSection>
                        )}

                        <div className="flex flex-wrap gap-2">
                            <Button type="button" variant="ghost" onClick={() => setAdvanced((v) => !v)}>
                                {advanced ? t('forms.hide_advanced') : t('forms.show_advanced')}
                            </Button>
                        </div>

                        {advanced && (
                            <FormSection title={t('forms.section_advanced')}>
                                <FormField label={t('tools.fields.definition')}>
                                    <Controller name="definition_json" control={control} render={({ field }) => <JsonField value={field.value} onChange={field.onChange} rows={12} />} />
                                </FormField>
                                <FormField label={t('tools.fields.config')}>
                                    <Controller name="config_json" control={control} render={({ field }) => <JsonField value={field.value} onChange={field.onChange} rows={4} />} />
                                </FormField>
                            </FormSection>
                        )}

                        <Button type="submit">{t('actions.save')}</Button>
                    </form>
                </CardBody>
            </Card>
        </FormContainer>
    );
}
