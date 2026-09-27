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
import { FormContainer } from '../components/ui/PageContainer';
import { PageHeader } from '../components/ui/PageHeader';

export type FieldDef =
    | { name: string; type: 'text' | 'textarea' | 'status' | 'slug' }
    | { name: string; type: 'comma-list' };

type Props = {
    apiBase: string;
    resourceBase: string;
    titleCreateKey: string;
    titleEditKey: string;
    formIntroKey?: string;
    fields: FieldDef[];
};

type FormValues = Record<string, string>;

export function ResourceFormPage({
    apiBase,
    resourceBase,
    titleCreateKey,
    titleEditKey,
    formIntroKey,
    fields,
}: Props) {
    const { slug } = useParams();
    const isEdit = Boolean(slug);
    const boot = useAdminConfig();
    const { t } = useI18n();
    const statusOptions = useStatusOptions();
    const navigate = useNavigate();
    const [error, setError] = useState<string | null>(null);
    const ph = (key: string) => t(`placeholders.${key}`);

    const { register, handleSubmit, control, reset } = useForm<FormValues>({
        defaultValues: { status: 'draft' },
    });

    useEffect(() => {
        if (!slug) return;
        adminApi
            .get<{ data: Record<string, unknown> }>(boot, `${apiBase}/${slug}`)
            .then((res) => {
                const d = res.data;
                const values: FormValues = {};
                fields.forEach((f) => {
                    if (f.type === 'status') {
                        values.status = String(d.status ?? 'draft');
                    } else if (f.type === 'comma-list') {
                        values[f.name] = Array.isArray(d[f.name]) ? (d[f.name] as string[]).join(', ') : '';
                    } else {
                        values[f.name] = String(d[f.name] ?? '');
                    }
                });
                reset(values);
            })
            .catch(() => setError('Load failed'));
    }, [boot, slug, fields, reset, apiBase]);

    const onSubmit = handleSubmit(async (values) => {
        setError(null);
        const payload: Record<string, unknown> = {};
        fields.forEach((f) => {
            if (f.type === 'status') {
                payload.status = values.status ?? 'draft';
            } else if (f.type === 'comma-list') {
                payload[f.name] = String(values[f.name] ?? '')
                    .split(',')
                    .map((s) => s.trim())
                    .filter(Boolean);
            } else {
                payload[f.name] = values[f.name];
            }
        });
        try {
            if (isEdit && slug) {
                await adminApi.put(boot, `${apiBase}/${slug}`, payload);
                navigate(`/${resourceBase}/${slug}`);
            } else {
                const res = await adminApi.post<{ data: { slug: string } }>(boot, apiBase, payload);
                navigate(`/${resourceBase}/${res.data.slug}`);
            }
        } catch (e) {
            setError(e instanceof Error ? e.message : 'Save failed');
        }
    });

    return (
        <FormContainer>
            <PageHeader
                title={isEdit ? t(titleEditKey, { name: slug ?? '' }) : t(titleCreateKey)}
                description={formIntroKey ? t(formIntroKey) : undefined}
            />
            {error && (
                <div className="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{error}</div>
            )}
            <Card>
                <CardBody>
                    <form onSubmit={onSubmit} className="space-y-5">
                        {fields.map((field) => (
                            <FormField
                                key={field.name}
                                label={t(`fields.${field.name}`)}
                                hint={field.type === 'slug' ? t('placeholders.slug_hint') : field.type === 'comma-list' ? t('placeholders.list_hint') : undefined}
                                required={field.name === 'name' || field.name === 'slug'}
                            >
                                {field.type === 'textarea' ? (
                                    <Textarea {...register(field.name)} rows={4} placeholder={ph(field.name)} />
                                ) : field.type === 'status' ? (
                                    <Controller
                                        name="status"
                                        control={control}
                                        render={({ field: f }) => (
                                            <NativeSelect
                                                value={f.value}
                                                onValueChange={f.onChange}
                                                options={statusOptions}
                                                placeholder={ph('status')}
                                            />
                                        )}
                                    />
                                ) : (
                                    <Input
                                        {...register(field.name)}
                                        readOnly={field.type === 'slug' && isEdit}
                                        placeholder={ph(field.name)}
                                    />
                                )}
                            </FormField>
                        ))}
                        <div className="flex flex-wrap gap-3 border-t border-slate-100 pt-6">
                            <Button type="submit">{isEdit ? t('actions.update') : t('actions.save')}</Button>
                            <Button type="button" variant="secondary" onClick={() => navigate(-1)}>
                                {t('actions.back')}
                            </Button>
                        </div>
                    </form>
                </CardBody>
            </Card>
        </FormContainer>
    );
}
