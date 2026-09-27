import { Controller, useForm } from 'react-hook-form';
import { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { adminApi } from '../lib/api';
import { useAdminConfig } from '../lib/config';
import { useStatusOptions } from '../lib/hooks';
import { useI18n } from '../lib/i18n';
import { Button } from '../components/ui/Button';
import { Card, CardBody } from '../components/ui/Card';
import { FormField } from '../components/ui/FormField';
import { Input, Textarea } from '../components/ui/Input';
import { NativeSelect } from '../components/ui/NativeSelect';
import { JsonField } from '../components/ui/JsonField';
import { FormContainer } from '../components/ui/PageContainer';
import { PageHeader } from '../components/ui/PageHeader';
import { Skeleton } from '../components/ui/Skeleton';

type FormValues = {
    name: string;
    slug: string;
    description: string;
    status: string;
    steps_json: string;
};

const STEPS_EXAMPLE = `[
  {
    "id": "start",
    "type": "set",
    "assign": { "greeting": "Hello" }
  },
  {
    "id": "done",
    "type": "complete"
  }
]`;

export function WorkflowFormPage() {
    const { slug } = useParams();
    const isEdit = Boolean(slug);
    const boot = useAdminConfig();
    const { t } = useI18n();
    const statusOptions = useStatusOptions();
    const navigate = useNavigate();
    const [error, setError] = useState<string | null>(null);
    const [loading, setLoading] = useState(isEdit);

    const { register, handleSubmit, control, reset } = useForm<FormValues>({
        defaultValues: { status: 'draft', steps_json: STEPS_EXAMPLE },
    });

    useEffect(() => {
        if (!slug) return;
        adminApi
            .get<{ data: Record<string, unknown> }>(boot, `/workflows/${slug}`)
            .then((res) => {
                const d = res.data;
                const steps = d.steps ?? (d.definition as Record<string, unknown> | undefined)?.steps ?? [];
                reset({
                    name: String(d.name ?? ''),
                    slug: String(d.slug ?? slug),
                    description: String(d.description ?? ''),
                    status: String(d.status ?? 'draft'),
                    steps_json: JSON.stringify(steps, null, 2),
                });
            })
            .catch(() => setError('Load failed'))
            .finally(() => setLoading(false));
    }, [boot, slug, reset]);

    const onSubmit = handleSubmit(async (values) => {
        setError(null);
        try {
            const steps = JSON.parse(values.steps_json) as unknown;
            if (!Array.isArray(steps) || steps.length === 0) {
                throw new Error('Steps must be a non-empty JSON array.');
            }
            const payload = {
                name: values.name,
                slug: values.slug,
                description: values.description || null,
                status: values.status,
                steps,
            };
            if (isEdit && slug) {
                await adminApi.put(boot, `/workflows/${slug}`, payload);
                navigate(`/workflows/${slug}`);
            } else {
                const res = await adminApi.post<{ data: { slug: string } }>(boot, '/workflows', payload);
                navigate(`/workflows/${res.data.slug}`);
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
                title={isEdit ? t('workflows.edit_heading', { name: slug ?? '' }) : t('workflows.create_heading')}
                description={t('workflows.steps_hint')}
            />
            {error && (
                <div className="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{error}</div>
            )}
            <Card>
                <CardBody>
                    <form onSubmit={onSubmit} className="space-y-5">
                        <FormField label={t('fields.name')} required>
                            <Input {...register('name', { required: true })} />
                        </FormField>
                        <FormField label={t('fields.slug')} required>
                            <Input {...register('slug', { required: true })} readOnly={isEdit} />
                        </FormField>
                        <FormField label={t('fields.description')}>
                            <Textarea {...register('description')} rows={2} />
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
                        <FormField label={t('workflows.fields.steps')} hint={t('workflows.steps_hint')}>
                            <Controller
                                name="steps_json"
                                control={control}
                                render={({ field }) => <JsonField value={field.value} onChange={field.onChange} rows={18} />}
                            />
                        </FormField>
                        <Button type="submit">{t('actions.save')}</Button>
                    </form>
                </CardBody>
            </Card>
        </FormContainer>
    );
}
