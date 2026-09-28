import { Controller, useForm, useWatch } from 'react-hook-form';
import { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { adminApi } from '../lib/api';
import { useAdminConfig } from '../lib/config';
import { useStatusOptions } from '../lib/hooks';
import { useI18n } from '../lib/i18n';
import { slugify } from '../lib/slugify';
import { SlugCheckboxList } from '../components/forms/SlugCheckboxList';
import { FormWizard } from '../components/forms/FormWizard';
import { FormField } from '../components/ui/FormField';
import { Input, Textarea } from '../components/ui/Input';
import { NativeSelect } from '../components/ui/NativeSelect';
import { ErrorBanner } from '../components/ui/DataTable';
import { FormContainer } from '../components/ui/PageContainer';
import { PageHeader } from '../components/ui/PageHeader';
import { Skeleton } from '../components/ui/Skeleton';

type FormValues = {
    name: string;
    slug: string;
    description: string;
    instructions: string;
    tools: string[];
    knowledge: string[];
    status: string;
};

export function SkillFormPage() {
    const { slug } = useParams();
    const isEdit = Boolean(slug);
    const boot = useAdminConfig();
    const { t } = useI18n();
    const statusOptions = useStatusOptions();
    const navigate = useNavigate();
    const [error, setError] = useState<string | null>(null);
    const [loading, setLoading] = useState(isEdit);
    const [step, setStep] = useState(0);
    const ph = (key: string) => t(`placeholders.${key}`);

    const { register, handleSubmit, control, reset, setValue } = useForm<FormValues>({
        defaultValues: { status: 'draft', tools: [], knowledge: [] },
    });

    const name = useWatch({ control, name: 'name' });
    useEffect(() => {
        if (!isEdit && name) setValue('slug', slugify(name));
    }, [name, isEdit, setValue]);

    useEffect(() => {
        if (!slug) return;
        adminApi
            .get<{ data: Record<string, unknown> }>(boot, `/skills/${slug}`)
            .then((res) => {
                const d = res.data;
                reset({
                    name: String(d.name ?? ''),
                    slug: String(d.slug ?? slug),
                    description: String(d.description ?? ''),
                    instructions: String(d.instructions ?? ''),
                    tools: Array.isArray(d.tools) ? (d.tools as string[]) : [],
                    knowledge: Array.isArray(d.knowledge) ? (d.knowledge as string[]) : [],
                    status: String(d.status ?? 'draft'),
                });
            })
            .catch(() => setError('Load failed'))
            .finally(() => setLoading(false));
    }, [boot, slug, reset]);

    const save = handleSubmit(async (values) => {
        setError(null);
        const payload = {
            name: values.name,
            slug: values.slug,
            description: values.description || null,
            instructions: values.instructions || null,
            status: values.status,
            tools: values.tools,
            knowledge: values.knowledge,
        };
        try {
            if (isEdit && slug) {
                await adminApi.put(boot, `/skills/${slug}`, payload);
                navigate(`/skills/${slug}`);
            } else {
                const res = await adminApi.post<{ data: { slug: string } }>(boot, '/skills', payload);
                navigate(`/skills/${res.data.slug}`);
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
                title={isEdit ? t('skills.edit_heading', { name: slug ?? '' }) : t('skills.create_heading')}
                description={t('skills.form_intro_simple')}
            />
            {error && <ErrorBanner message={error} />}
            <FormWizard
                steps={[
                    { id: 'basics', title: t('wizard.basics'), description: t('wizard.basics_desc') },
                    { id: 'attach', title: t('wizard.attach'), description: t('wizard.attach_desc') },
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
                        <FormField label={t('fields.slug')} required hint={t('placeholders.slug_auto')}>
                            <Input {...register('slug', { required: true })} readOnly={isEdit} />
                        </FormField>
                        <FormField label={t('fields.status')}>
                            <Controller
                                name="status"
                                control={control}
                                render={({ field }) => <NativeSelect value={field.value} onValueChange={field.onChange} options={statusOptions} />}
                            />
                        </FormField>
                        <div className="lg:col-span-2">
                            <FormField label={t('fields.description')}>
                                <Textarea {...register('description')} rows={2} />
                            </FormField>
                        </div>
                        <div className="lg:col-span-2">
                            <FormField label={t('fields.instructions')}>
                                <Textarea {...register('instructions')} rows={5} placeholder={ph('instructions')} />
                            </FormField>
                        </div>
                    </div>
                )}
                {step === 1 && (
                    <div className="grid gap-6 lg:grid-cols-2">
                        <FormField label={t('fields.tools')}>
                            <Controller
                                name="tools"
                                control={control}
                                render={({ field }) => (
                                    <SlugCheckboxList apiPath="/tools" value={field.value} onChange={field.onChange} emptyHint={t('forms.empty_tools')} />
                                )}
                            />
                        </FormField>
                        <FormField label={t('fields.knowledge')}>
                            <Controller
                                name="knowledge"
                                control={control}
                                render={({ field }) => (
                                    <SlugCheckboxList
                                        apiPath="/knowledge-sources"
                                        value={field.value}
                                        onChange={field.onChange}
                                        emptyHint={t('forms.empty_knowledge')}
                                    />
                                )}
                            />
                        </FormField>
                    </div>
                )}
            </FormWizard>
        </FormContainer>
    );
}
