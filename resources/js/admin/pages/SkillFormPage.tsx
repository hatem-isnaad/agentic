import { Controller, useForm, useWatch } from 'react-hook-form';
import { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { adminApi } from '../lib/api';
import { useAdminConfig } from '../lib/config';
import { useStatusOptions } from '../lib/hooks';
import { useI18n } from '../lib/i18n';
import { slugify } from '../lib/slugify';
import { SlugCheckboxList } from '../components/forms/SlugCheckboxList';
import { Button } from '../components/ui/Button';
import { Card, CardBody } from '../components/ui/Card';
import { FormField } from '../components/ui/FormField';
import { Input, Textarea } from '../components/ui/Input';
import { NativeSelect } from '../components/ui/NativeSelect';
import { FormSection } from '../components/ui/FormSection';
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

    const onSubmit = handleSubmit(async (values) => {
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
            {error && (
                <div className="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{error}</div>
            )}
            <Card>
                <CardBody>
                    <form onSubmit={onSubmit} className="space-y-6">
                        <FormSection title={t('forms.section_basics')}>
                            <FormField label={t('fields.name')} required>
                                <Input {...register('name', { required: true })} />
                            </FormField>
                            <FormField label={t('fields.slug')} required hint={t('placeholders.slug_auto')}>
                                <Input {...register('slug', { required: true })} readOnly={isEdit} />
                            </FormField>
                            <FormField label={t('fields.description')}>
                                <Textarea {...register('description')} rows={2} />
                            </FormField>
                            <FormField label={t('fields.instructions')}>
                                <Textarea {...register('instructions')} rows={5} placeholder={ph('instructions')} />
                            </FormField>
                            <FormField label={t('fields.status')}>
                                <Controller
                                    name="status"
                                    control={control}
                                    render={({ field }) => (
                                        <NativeSelect value={field.value} onValueChange={field.onChange} options={statusOptions} />
                                    )}
                                />
                            </FormField>
                        </FormSection>
                        <FormSection title={t('forms.section_attach')} description={t('skills.attach_desc')}>
                            <FormField label={t('fields.tools')}>
                                <Controller
                                    name="tools"
                                    control={control}
                                    render={({ field }) => (
                                        <SlugCheckboxList
                                            apiPath="/tools"
                                            value={field.value}
                                            onChange={field.onChange}
                                            emptyHint={t('forms.empty_tools')}
                                        />
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
                        </FormSection>
                        <Button type="submit">{t('actions.save')}</Button>
                    </form>
                </CardBody>
            </Card>
        </FormContainer>
    );
}
