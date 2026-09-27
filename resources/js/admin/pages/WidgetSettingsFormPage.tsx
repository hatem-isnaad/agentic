import { useEffect, useState } from 'react';
import { Controller, useForm } from 'react-hook-form';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { adminApi } from '../lib/api';
import { useAdminConfig } from '../lib/config';
import { useI18n } from '../lib/i18n';
import { Button } from '../components/ui/Button';
import { Card, CardBody } from '../components/ui/Card';
import { FormField } from '../components/ui/FormField';
import { Input, Textarea } from '../components/ui/Input';
import { NativeSelect } from '../components/ui/NativeSelect';
import { FormContainer } from '../components/ui/PageContainer';
import { PageHeader } from '../components/ui/PageHeader';
import { Skeleton } from '../components/ui/Skeleton';

type WidgetSettings = {
    auth_mode?: string;
    locale?: string;
    agent_language?: string;
    intake_enabled?: boolean;
    welcome_message?: string;
    intake_questions?: string[];
    theme?: Record<string, unknown>;
    reply_formats?: string[];
};

type FormValues = {
    auth_mode: string;
    locale: string;
    agent_language: string;
    intake_enabled: string;
    welcome_message: string;
    intake_questions: string;
    reply_formats: string;
    theme_json: string;
};

function settingsToForm(s: WidgetSettings): FormValues {
    return {
        auth_mode: s.auth_mode ?? 'both',
        locale: s.locale ?? 'en',
        agent_language: s.agent_language ?? '',
        intake_enabled: s.intake_enabled ? '1' : '0',
        welcome_message: s.welcome_message ?? '',
        intake_questions: Array.isArray(s.intake_questions) ? s.intake_questions.join('\n') : '',
        reply_formats: Array.isArray(s.reply_formats) ? s.reply_formats.join(', ') : 'blocks',
        theme_json: JSON.stringify(s.theme ?? {}, null, 2),
    };
}

function formToSettings(values: FormValues): WidgetSettings {
    let theme: Record<string, unknown> = {};
    const rawTheme = values.theme_json.trim();
    if (rawTheme !== '') {
        const parsed = JSON.parse(rawTheme) as unknown;
        if (typeof parsed !== 'object' || parsed === null || Array.isArray(parsed)) {
            throw new Error('Theme must be a JSON object.');
        }
        theme = parsed as Record<string, unknown>;
    }

    return {
        auth_mode: values.auth_mode,
        locale: values.locale,
        agent_language: values.agent_language.trim() === '' ? undefined : values.agent_language.trim(),
        intake_enabled: values.intake_enabled === '1',
        welcome_message: values.welcome_message.trim() === '' ? undefined : values.welcome_message,
        intake_questions: values.intake_questions
            .split('\n')
            .map((line) => line.trim())
            .filter(Boolean),
        reply_formats: values.reply_formats
            .split(',')
            .map((s) => s.trim())
            .filter(Boolean),
        theme,
    };
}

export function WidgetSettingsFormPage() {
    const { agentSlug = '' } = useParams();
    const boot = useAdminConfig();
    const { t } = useI18n();
    const navigate = useNavigate();
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [hasOverride, setHasOverride] = useState(false);

    const { register, handleSubmit, control, reset } = useForm<FormValues>();

    useEffect(() => {
        if (!agentSlug) return;
        adminApi
            .get<{ data: { settings: WidgetSettings } }>(boot, `/widget-settings/${agentSlug}`)
            .then((res) => {
                reset(settingsToForm(res.data.settings));
            })
            .catch(() => setError('Load failed'))
            .finally(() => setLoading(false));

        adminApi
            .get<{ data: { agent_slug: string }[] }>(boot, '/widget-settings?per_page=100')
            .then((res) => setHasOverride(res.data.some((r) => r.agent_slug === agentSlug)))
            .catch(() => setHasOverride(false));
    }, [boot, agentSlug, reset]);

    const onSubmit = handleSubmit(async (values) => {
        setError(null);
        try {
            const settings = formToSettings(values);
            await adminApi.put(boot, `/widget-settings/${agentSlug}`, { settings });
            navigate('/widget-settings');
        } catch (e) {
            setError(e instanceof Error ? e.message : 'Save failed');
        }
    });

    const onResetOverrides = async () => {
        if (!window.confirm(t('actions.confirm_delete'))) return;
        setError(null);
        try {
            await adminApi.delete(boot, `/widget-settings/${agentSlug}`);
            navigate('/widget-settings');
        } catch (e) {
            setError(e instanceof Error ? e.message : 'Delete failed');
        }
    };

    const authOptions = [
        { value: 'guest', label: t('widget_settings.auth_guest') },
        { value: 'auth', label: t('widget_settings.auth_required') },
        { value: 'both', label: t('widget_settings.auth_both') },
    ];

    return (
        <FormContainer>
            <PageHeader
                title={t('widget_settings.edit_heading', { agent: agentSlug })}
                description={t('widget_settings.json_hint')}
                actions={
                    <Link to="/widget-settings">
                        <Button type="button" variant="secondary">{t('actions.back')}</Button>
                    </Link>
                }
            />

            {loading ? (
                <Skeleton className="h-64 w-full" />
            ) : (
                <>
                    {error && (
                        <div className="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                            {error}
                        </div>
                    )}
                    <Card>
                        <CardBody>
                            <form onSubmit={onSubmit} className="space-y-5">
                                <FormField label={t('widget_settings.fields.auth_mode')}>
                                    <Controller
                                        name="auth_mode"
                                        control={control}
                                        render={({ field }) => (
                                            <NativeSelect
                                                value={field.value}
                                                onValueChange={field.onChange}
                                                options={authOptions}
                                            />
                                        )}
                                    />
                                </FormField>
                                <div className="grid gap-5 sm:grid-cols-2">
                                    <FormField label={t('widget_settings.fields.locale')}>
                                        <Input {...register('locale')} />
                                    </FormField>
                                    <FormField label={t('widget_settings.fields.agent_language')}>
                                        <Input {...register('agent_language')} placeholder="en" />
                                    </FormField>
                                </div>
                                <FormField label={t('widget_settings.fields.intake_enabled')}>
                                    <Controller
                                        name="intake_enabled"
                                        control={control}
                                        render={({ field }) => (
                                            <NativeSelect
                                                value={field.value}
                                                onValueChange={field.onChange}
                                                options={[
                                                    { value: '0', label: t('widget_settings.no') },
                                                    { value: '1', label: t('widget_settings.yes') },
                                                ]}
                                            />
                                        )}
                                    />
                                </FormField>
                                <FormField label={t('widget_settings.fields.welcome_message')}>
                                    <Textarea {...register('welcome_message')} rows={3} />
                                </FormField>
                                <FormField
                                    label={t('widget_settings.fields.intake_questions')}
                                    hint={t('widget_settings.intake_questions_hint')}
                                >
                                    <Textarea {...register('intake_questions')} rows={4} />
                                </FormField>
                                <FormField label={t('widget_settings.fields.reply_formats')} hint={t('placeholders.list_hint')}>
                                    <Input {...register('reply_formats')} />
                                </FormField>
                                <FormField label={t('widget_settings.fields.theme')} hint={t('widget_settings.theme_hint')}>
                                    <Textarea {...register('theme_json')} rows={8} className="font-mono text-xs" />
                                </FormField>
                                <div className="flex flex-wrap gap-3 pt-2">
                                    <Button type="submit">{t('actions.update')}</Button>
                                    {hasOverride && (
                                        <Button type="button" variant="danger" onClick={onResetOverrides}>
                                            {t('widget_settings.reset_to_default')}
                                        </Button>
                                    )}
                                </div>
                            </form>
                        </CardBody>
                    </Card>
                </>
            )}
        </FormContainer>
    );
}
