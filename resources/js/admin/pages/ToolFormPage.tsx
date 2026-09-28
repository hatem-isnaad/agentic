import { Controller, useForm, useWatch } from 'react-hook-form';
import { useEffect, useMemo, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { adminApi } from '../lib/api';
import { useAdminConfig } from '../lib/config';
import { useStatusOptions } from '../lib/hooks';
import { useI18n } from '../lib/i18n';
import { slugify } from '../lib/slugify';
import { draftFromDefinition, emptyDraft, serializeDraft, type HttpToolDraft } from '../lib/httpToolBuilder';
import { FormWizard } from '../components/forms/FormWizard';
import { FormField } from '../components/ui/FormField';
import { Input, Textarea } from '../components/ui/Input';
import { NativeSelect } from '../components/ui/NativeSelect';
import { JsonField, parseJsonObject } from '../components/ui/JsonField';
import { HelpCallout } from '../components/ui/HelpCallout';
import { ErrorBanner } from '../components/ui/DataTable';
import { FormContainer } from '../components/ui/PageContainer';
import { PageHeader } from '../components/ui/PageHeader';
import { Skeleton } from '../components/ui/Skeleton';
import { HttpToolBuilder } from '../components/tools/HttpToolBuilder';
import { HttpToolTestPanel } from '../components/tools/HttpToolTestPanel';

type FormValues = {
    name: string;
    slug: string;
    description: string;
    driver: string;
    status: string;
    publish: string;
    definition_json: string;
    config_json: string;
};

const DRIVER_OPTIONS = [
    { value: 'http', label: 'HTTP API' },
    { value: 'mcp', label: 'MCP (sync first)' },
    { value: 'code', label: 'Code (PHP handler)' },
];

export function ToolFormPage() {
    const { slug } = useParams();
    const isEdit = Boolean(slug);
    const boot = useAdminConfig();
    const { t } = useI18n();
    const statusOptions = useStatusOptions();
    const navigate = useNavigate();
    const [error, setError] = useState<string | null>(null);
    const [loading, setLoading] = useState(isEdit);
    const [step, setStep] = useState(0);
    const [codeHandlers, setCodeHandlers] = useState<{ handler: string; description: string; input_schema: Record<string, unknown> }[]>([]);
    const [selectedHandler, setSelectedHandler] = useState('');
    const [connections, setConnections] = useState<{ slug: string; name: string; type: string }[]>([]);
    const [httpDraft, setHttpDraft] = useState<HttpToolDraft>(emptyDraft);

    const { register, handleSubmit, control, reset, setValue } = useForm<FormValues>({
        defaultValues: {
            driver: 'http',
            status: 'draft',
            publish: '1',
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
        adminApi
            .get<{ data: { slug: string; name: string; type: string }[] }>(boot, '/connections?per_page=100')
            .then((res) => setConnections(res.data))
            .catch(() => setConnections([]));
    }, [boot]);

    useEffect(() => {
        if (driver !== 'code') return;
        adminApi
            .get<{ data: { handler: string; description: string; input_schema: Record<string, unknown> }[] }>(boot, '/code-handlers')
            .then((res) => setCodeHandlers(res.data))
            .catch(() => setCodeHandlers([]));
    }, [boot, driver]);

    useEffect(() => {
        if (!slug) return;
        let cancelled = false;
        adminApi
            .get<{ data: Record<string, unknown> }>(boot, `/tools/${slug}`)
            .then((res) => {
                if (cancelled) {
                    return;
                }
                const d = res.data;
                const def = (d.definition ?? {}) as Record<string, unknown>;
                const config = (d.config ?? {}) as Record<string, unknown>;
                reset({
                    name: String(d.name ?? ''),
                    slug: String(d.slug ?? slug),
                    description: String(d.description ?? ''),
                    driver: String(d.driver ?? 'http'),
                    status: String(d.status ?? 'draft'),
                    publish: '1',
                    definition_json: JSON.stringify(def, null, 2),
                    config_json: JSON.stringify(config, null, 2),
                });
                if (String(d.driver ?? 'http') === 'http') {
                    setHttpDraft(draftFromDefinition(def, config));
                }
                if (typeof def.handler === 'string') {
                    setSelectedHandler(def.handler);
                }
            })
            .catch(() => {
                if (!cancelled) {
                    setError('Load failed');
                }
            })
            .finally(() => {
                if (!cancelled) {
                    setLoading(false);
                }
            });

        return () => {
            cancelled = true;
        };
    }, [boot, slug, reset]);

    const save = handleSubmit(async (values) => {
        setError(null);
        try {
            let definition: Record<string, unknown>;
            if (values.driver === 'http') {
                definition = serializeDraft(httpDraft);
            } else if (values.driver === 'code' && selectedHandler) {
                const meta = codeHandlers.find((h) => h.handler === selectedHandler);
                definition = {
                    handler: selectedHandler,
                    input_schema: meta?.input_schema ?? { type: 'object', properties: {} },
                };
            } else {
                definition = parseJsonObject(values.definition_json, 'Definition');
            }
            const payload = {
                name: values.name,
                slug: values.slug,
                description: values.description || null,
                driver: values.driver,
                status: values.status,
                config: {},
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

    const steps = useMemo(
        () => [
            { id: 'basics', title: t('wizard.basics'), description: t('wizard.basics_desc') },
            { id: 'build', title: t('wizard.build'), description: t('wizard.build_desc') },
            { id: 'review', title: t('wizard.review'), description: t('wizard.review_desc') },
        ],
        [t],
    );

    const canNext = step === 0 ? Boolean(name?.trim()) : step === 1 ? (driver !== 'http' || httpDraft.url.trim() !== '') : true;

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
            {error && <ErrorBanner message={error} />}
            <FormWizard
                steps={steps}
                step={step}
                onStepChange={setStep}
                onSubmit={() => void save()}
                canNext={canNext}
                saveLabel={isEdit ? t('actions.update') : t('actions.save')}
            >
                {step === 0 && (
                    <div className="grid gap-5 lg:grid-cols-2">
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
                            <Textarea {...register('description')} rows={3} className="min-h-[88px]" />
                        </FormField>
                    </div>
                )}
                {step === 1 && (
                    <div className="space-y-4">
                        {driver === 'mcp' && (
                            <HelpCallout>
                                {t('tools.mcp_hint')}{' '}
                                <Link to="/mcp-servers" className="font-semibold text-brand-700 underline">
                                    {t('nav.mcp')}
                                </Link>
                            </HelpCallout>
                        )}
                        {driver === 'code' && (
                            <>
                                <HelpCallout>
                                    {t('tools.code_hint')}{' '}
                                    <Link to="/custom-code-tools" className="font-semibold text-brand-700 underline">
                                        {t('nav.custom_code_tools')}
                                    </Link>
                                </HelpCallout>
                                <FormField label={t('custom_tools.col_handler')}>
                                    <NativeSelect
                                        value={selectedHandler || '__none__'}
                                        onValueChange={(v) => {
                                            setSelectedHandler(v === '__none__' ? '' : v);
                                            const meta = codeHandlers.find((h) => h.handler === v);
                                            if (meta && !isEdit) {
                                                setValue('name', meta.description);
                                                setValue('slug', slugify(meta.description));
                                            }
                                        }}
                                        options={[
                                            { value: '__none__', label: t('custom_tools.select_handler') },
                                            ...codeHandlers.map((h) => ({ value: h.handler, label: `${h.handler} — ${h.description}` })),
                                        ]}
                                    />
                                </FormField>
                            </>
                        )}
                        {driver === 'http' && <HttpToolBuilder draft={httpDraft} connections={connections} onChange={setHttpDraft} />}
                    </div>
                )}
                {step === 2 && (
                    <div className="grid gap-5 lg:grid-cols-2">
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
                        <div className="lg:col-span-2">
                            <FormField label={t('tools.fields.definition')} hint={t('tools.definition_hint')}>
                                <Controller
                                    name="definition_json"
                                    control={control}
                                    render={({ field }) => (
                                        <JsonField
                                            value={driver === 'http' ? JSON.stringify(serializeDraft(httpDraft), null, 2) : field.value}
                                            onChange={field.onChange}
                                            rows={10}
                                        />
                                    )}
                                />
                            </FormField>
                        </div>
                    </div>
                )}
            </FormWizard>
            {isEdit && slug && driver === 'http' && (
                <div className="mt-8">
                    <HttpToolTestPanel
                        slug={slug}
                        inputSchema={serializeDraft(httpDraft).input_schema as Record<string, unknown> | undefined}
                    />
                </div>
            )}
        </FormContainer>
    );
}
