import { Controller, useForm, useWatch } from 'react-hook-form';
import { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { adminApi } from '../lib/api';
import { useAdminConfig } from '../lib/config';
import { useStatusOptions } from '../lib/hooks';
import { useI18n } from '../lib/i18n';
import { FormWizard } from '../components/forms/FormWizard';
import { FormField } from '../components/ui/FormField';
import { Input, Textarea } from '../components/ui/Input';
import { NativeSelect } from '../components/ui/NativeSelect';
import { JsonField, parseJsonObject } from '../components/ui/JsonField';
import { ErrorBanner } from '../components/ui/DataTable';
import { FormContainer } from '../components/ui/PageContainer';
import { PageHeader } from '../components/ui/PageHeader';
import { Skeleton } from '../components/ui/Skeleton';
import { slugify } from '../lib/slugify';

type FormValues = {
    name: string;
    slug: string;
    description: string;
    driver: string;
    status: string;
    config_json: string;
};

const DRIVER_OPTIONS = [
    { value: 'vector', label: 'vector (RAG)' },
    { value: 'array', label: 'array (keyword)' },
];

export function KnowledgeSourceFormPage() {
    const { slug } = useParams();
    const isEdit = Boolean(slug);
    const boot = useAdminConfig();
    const { t } = useI18n();
    const statusOptions = useStatusOptions();
    const navigate = useNavigate();
    const [error, setError] = useState<string | null>(null);
    const [loading, setLoading] = useState(isEdit);
    const [step, setStep] = useState(0);
    const { register, handleSubmit, control, reset, setValue } = useForm<FormValues>({
        defaultValues: { driver: 'vector', status: 'published', config_json: '{"documents":[]}' },
    });
    const name = useWatch({ control, name: 'name' });
    useEffect(() => {
        if (!isEdit && name) setValue('slug', slugify(name));
    }, [name, isEdit, setValue]);

    useEffect(() => {
        if (!slug) return;
        adminApi
            .get<{ data: Record<string, unknown> }>(boot, `/knowledge-sources/${slug}`)
            .then((res) => {
                const d = res.data;
                reset({
                    name: String(d.name ?? ''),
                    slug: String(d.slug ?? slug),
                    description: String(d.description ?? ''),
                    driver: String(d.driver ?? 'vector'),
                    status: String(d.status ?? 'draft'),
                    config_json: JSON.stringify(d.config ?? {}, null, 2),
                });
            })
            .catch(() => setError('Load failed'))
            .finally(() => setLoading(false));
    }, [boot, slug, reset]);

    const save = handleSubmit(async (values) => {
        setError(null);
        try {
            const config = parseJsonObject(values.config_json, 'Config');
            const payload = {
                name: values.name,
                slug: values.slug,
                description: values.description || null,
                driver: values.driver,
                status: values.status,
                config,
            };
            if (isEdit && slug) {
                await adminApi.put(boot, `/knowledge-sources/${slug}`, payload);
                navigate(`/knowledge-sources/${slug}`);
            } else {
                const res = await adminApi.post<{ data: { slug: string } }>(boot, '/knowledge-sources', payload);
                navigate(`/knowledge-sources/${res.data.slug}`);
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
            <PageHeader
                title={isEdit ? t('knowledge.edit_heading', { name: slug ?? '' }) : t('knowledge.create_heading')}
                description={t('knowledge.form_intro_simple')}
            />
            {error && <ErrorBanner message={error} />}
            <FormWizard
                steps={[
                    { id: 'basics', title: t('wizard.basics'), description: t('wizard.basics_desc') },
                    { id: 'source', title: t('wizard.source'), description: t('wizard.source_desc') },
                ]}
                step={step}
                onStepChange={setStep}
                onSubmit={() => void save()}
                canNext={Boolean(name?.trim())}
                saveLabel={isEdit ? t('actions.update') : t('actions.save')}
            >
                {step === 0 && (
                    <div className="grid gap-5 lg:grid-cols-2">
                        <FormField label={t('fields.name')} required>
                            <Input {...register('name', { required: true })} />
                        </FormField>
                        <FormField label={t('fields.slug')} required>
                            <Input {...register('slug', { required: true })} readOnly={isEdit} />
                        </FormField>
                        <div className="lg:col-span-2">
                            <FormField label={t('fields.description')}>
                                <Textarea {...register('description')} rows={3} />
                            </FormField>
                        </div>
                    </div>
                )}
                {step === 1 && (
                    <div className="grid gap-5 lg:grid-cols-2">
                        <FormField label={t('fields.driver')} required>
                            <Controller
                                name="driver"
                                control={control}
                                render={({ field }) => <NativeSelect value={field.value} onValueChange={field.onChange} options={DRIVER_OPTIONS} />}
                            />
                        </FormField>
                        <FormField label={t('fields.status')}>
                            <Controller
                                name="status"
                                control={control}
                                render={({ field }) => <NativeSelect value={field.value} onValueChange={field.onChange} options={statusOptions} />}
                            />
                        </FormField>
                        <div className="lg:col-span-2">
                            <FormField label={t('knowledge.fields.config')} hint={t('knowledge.config_hint')}>
                                <Controller
                                    name="config_json"
                                    control={control}
                                    render={({ field }) => <JsonField value={field.value} onChange={field.onChange} rows={8} />}
                                />
                            </FormField>
                        </div>
                    </div>
                )}
            </FormWizard>
        </FormContainer>
    );
}
