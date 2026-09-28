import { useEffect, useMemo, useState } from 'react';
import { useForm, Controller, useWatch } from 'react-hook-form';
import { useNavigate, useParams } from 'react-router-dom';
import { adminApi } from '../lib/api';
import { useAdminConfig } from '../lib/config';
import { useStatusOptions } from '../lib/hooks';
import { useI18n } from '../lib/i18n';
import { FormField } from '../components/ui/FormField';
import { Input, Textarea } from '../components/ui/Input';
import { NativeSelect } from '../components/ui/NativeSelect';
import { FormContainer } from '../components/ui/PageContainer';
import { PageHeader } from '../components/ui/PageHeader';
import { Skeleton } from '../components/ui/Skeleton';
import { JsonField, parseJsonObject } from '../components/ui/JsonField';
import { ErrorBanner } from '../components/ui/DataTable';
import { FormWizard } from '../components/forms/FormWizard';
import { SlugCheckboxList } from '../components/forms/SlugCheckboxList';
import { slugify } from '../lib/slugify';

type RegistryProvider = { key: string; label: string; models: string[] };
type PersonaPresets = {
    genders: string[];
    languages: string[];
    dialects: string[];
    tones: string[];
    name_suggestions?: { male?: string[]; female?: string[] };
};

const PERSONA_NONE = '__none__';

type FormValues = {
    name: string;
    slug: string;
    description: string;
    instructions: string;
    persona_display_name: string;
    persona_gender: string;
    persona_language: string;
    persona_dialect: string;
    persona_tone: string;
    persona_notes: string;
    provider: string;
    model: string;
    skills: string[];
    tools: string[];
    knowledge: string[];
    permissions: string;
    temperature: string;
    max_tokens: string;
    config_json: string;
    status: string;
};

export function AgentFormPage() {
    const { slug } = useParams();
    const isEdit = Boolean(slug);
    const boot = useAdminConfig();
    const { t } = useI18n();
    const statusOptions = useStatusOptions();
    const navigate = useNavigate();
    const [error, setError] = useState<string | null>(null);
    const [registry, setRegistry] = useState<RegistryProvider[]>([]);
    const [personaPresets, setPersonaPresets] = useState<PersonaPresets>({
        genders: ['male', 'female', 'unspecified'],
        languages: ['en', 'ar', 'bilingual'],
        dialects: ['msa', 'saudi', 'egyptian', 'gulf', 'levant'],
        tones: ['friendly', 'formal', 'casual', 'professional', 'warm'],
        name_suggestions: {
            male: ['Ahmed', 'Mohamed', 'Omar', 'Khalid', 'Youssef', 'Faisal', 'Hassan'],
            female: ['Sara', 'Fatima', 'Noura', 'Layla', 'Mona', 'Hana', 'Reem'],
        },
    });
    const [registryReady, setRegistryReady] = useState(false);
    const [step, setStep] = useState(0);
    const ph = (key: string) => t(`placeholders.${key}`);

    const { register, handleSubmit, control, reset, setValue } = useForm<FormValues>({
        defaultValues: {
            name: '',
            slug: '',
            description: '',
            instructions: '',
            persona_display_name: '',
            persona_gender: PERSONA_NONE,
            persona_language: PERSONA_NONE,
            persona_dialect: PERSONA_NONE,
            persona_tone: PERSONA_NONE,
            persona_notes: '',
            provider: '',
            model: '',
            skills: [],
            tools: [],
            knowledge: [],
            permissions: '',
            temperature: '',
            max_tokens: '',
            config_json: '{}',
            status: 'draft',
        },
    });

    const providerValue = useWatch({ control, name: 'provider' });
    const nameValue = useWatch({ control, name: 'name' });
    const genderValue = useWatch({ control, name: 'persona_gender' });

    useEffect(() => {
        if (!isEdit && nameValue) {
            setValue('slug', slugify(nameValue));
        }
    }, [nameValue, isEdit, setValue]);

    const providerOptions = useMemo(
        () => registry.map((p) => ({ value: p.key, label: p.label })),
        [registry],
    );

    const modelOptions = useMemo(() => {
        const entry = registry.find((p) => p.key === providerValue);
        return (entry?.models ?? []).map((m) => ({ value: m, label: m }));
    }, [registry, providerValue]);

    useEffect(() => {
        adminApi
            .get<{
                data: {
                    providers: RegistryProvider[];
                    default_provider: string | null;
                    default_model: string | null;
                    persona?: PersonaPresets;
                };
            }>(boot, '/ai-registry')
            .then((res) => {
                setRegistry(res.data.providers);
                if (res.data.persona) {
                    setPersonaPresets(res.data.persona);
                }
                if (!slug && res.data.providers.length > 0) {
                    const defaultKey =
                        res.data.providers.find((p) => p.key === res.data.default_provider)?.key ??
                        res.data.providers[0].key;
                    const models = res.data.providers.find((p) => p.key === defaultKey)?.models ?? [];
                    const defaultModel =
                        (res.data.default_model && models.includes(res.data.default_model)
                            ? res.data.default_model
                            : models[0]) ?? '';
                    reset((current) => ({
                        ...current,
                        provider: defaultKey,
                        model: defaultModel,
                    }));
                }
            })
            .catch(() => setRegistry([]))
            .finally(() => setRegistryReady(true));
    }, [boot, slug, reset]);

    useEffect(() => {
        if (!slug) return;
        adminApi
            .get<{ data: Record<string, unknown> }>(boot, `/agents/${slug}`)
            .then((res) => {
                const d = res.data;
                let provider = String(d.provider ?? '');
                let model = String(d.model ?? '');
                if (provider && !registry.some((p) => p.key === provider)) {
                    provider = registry[0]?.key ?? provider;
                }
                const models = registry.find((p) => p.key === provider)?.models ?? [];
                if (model && models.length > 0 && !models.includes(model)) {
                    model = models[0] ?? model;
                }
                const persona =
                    d.config && typeof d.config === 'object' && !Array.isArray(d.config)
                        ? ((d.config as Record<string, unknown>).persona as Record<string, unknown> | undefined)
                        : undefined;
                reset({
                    name: String(d.name ?? ''),
                    slug: String(d.slug ?? ''),
                    description: String(d.description ?? ''),
                    instructions: String(d.instructions ?? ''),
                    persona_display_name: String(persona?.display_name ?? ''),
                    persona_gender: String(persona?.gender ?? PERSONA_NONE),
                    persona_language: String(persona?.language ?? PERSONA_NONE),
                    persona_dialect: String(persona?.dialect ?? PERSONA_NONE),
                    persona_tone: String(persona?.tone ?? PERSONA_NONE),
                    persona_notes: String(persona?.notes ?? ''),
                    provider,
                    model,
                    skills: Array.isArray(d.skills) ? (d.skills as string[]) : [],
                    tools: Array.isArray(d.tools) ? (d.tools as string[]) : [],
                    knowledge: Array.isArray(d.knowledge) ? (d.knowledge as string[]) : [],
                    permissions: Array.isArray(d.permissions) ? d.permissions.join(', ') : '',
                    temperature: d.temperature != null ? String(d.temperature) : '',
                    max_tokens: d.max_tokens != null ? String(d.max_tokens) : '',
                    config_json: JSON.stringify(d.config ?? {}, null, 2),
                    status: String(d.status ?? 'draft'),
                });
            })
            .catch(() => setError('Load failed'));
    }, [boot, slug, reset, registry]);

    const onSubmit = handleSubmit(async (values) => {
        setError(null);
        let config: Record<string, unknown> = {};
        try {
            config = parseJsonObject(values.config_json, 'Config');
            const persona = Object.fromEntries(
                Object.entries({
                    display_name: values.persona_display_name.trim(),
                    gender: values.persona_gender === PERSONA_NONE ? '' : values.persona_gender,
                    language: values.persona_language === PERSONA_NONE ? '' : values.persona_language,
                    dialect: values.persona_dialect === PERSONA_NONE ? '' : values.persona_dialect,
                    tone: values.persona_tone === PERSONA_NONE ? '' : values.persona_tone,
                    notes: values.persona_notes.trim(),
                }).filter(([, value]) => value !== ''),
            );
            if (Object.keys(persona).length > 0) {
                config.persona = persona;
            } else {
                delete config.persona;
            }
        } catch (e) {
            setError(e instanceof Error ? e.message : 'Invalid config JSON');
            return;
        }
        const payload: Record<string, unknown> = {
            name: values.name,
            slug: values.slug,
            description: values.description,
            instructions: values.instructions,
            provider: values.provider || null,
            model: values.model || null,
            skills: values.skills,
            tools: values.tools,
            knowledge: values.knowledge,
            permissions: values.permissions.split(',').map((s) => s.trim()).filter(Boolean),
            status: values.status,
            config,
        };
        if (values.temperature.trim() !== '') payload.temperature = parseFloat(values.temperature);
        if (values.max_tokens.trim() !== '') payload.max_tokens = parseInt(values.max_tokens, 10);
        try {
            if (isEdit && slug) {
                await adminApi.put(boot, `/agents/${slug}`, payload);
                navigate(`/agents/${slug}`);
            } else {
                const res = await adminApi.post<{ data: { slug: string } }>(boot, '/agents', payload);
                navigate(`/agents/${res.data.slug}`);
            }
        } catch (e) {
            setError(e instanceof Error ? e.message : 'Save failed');
        }
    });

    const onProviderChange = (next: string) => {
        setValue('provider', next);
        const models = registry.find((p) => p.key === next)?.models ?? [];
        setValue('model', models[0] ?? '');
    };

    const wizardSteps = [
        { id: 'basics', title: t('wizard.basics'), description: t('wizard.basics_desc') },
        { id: 'voice', title: t('wizard.voice'), description: t('wizard.voice_desc') },
        { id: 'model', title: t('wizard.model'), description: t('wizard.model_desc') },
        { id: 'attach', title: t('wizard.attach'), description: t('wizard.attach_desc') },
        { id: 'review', title: t('wizard.review'), description: t('wizard.review_desc') },
    ];

    return (
        <FormContainer>
            <PageHeader
                title={isEdit ? t('agents.edit_heading', { name: slug ?? '' }) : t('agents.create_heading')}
                description={t('forms.agent_intro_simple')}
            />
            {error && <ErrorBanner message={error} />}
            <FormWizard
                steps={wizardSteps}
                step={step}
                onStepChange={setStep}
                onSubmit={() => void onSubmit()}
                canNext={step === 0 ? Boolean(nameValue?.trim()) : true}
                submitting={!registryReady}
                saveLabel={isEdit ? t('actions.update') : t('actions.save')}
            >
                {step === 0 && (
                    <div className="space-y-5">
                        <div className="grid gap-5 lg:grid-cols-2">
                            <FormField label={t('fields.name')} required>
                                <Input {...register('name', { required: true })} placeholder={ph('name')} />
                            </FormField>
                            <FormField label={t('fields.slug')} required hint={t('placeholders.slug_auto')}>
                                <Input {...register('slug', { required: true })} readOnly={isEdit} placeholder={ph('slug')} />
                            </FormField>
                            <FormField label={t('fields.status')}>
                                <Controller
                                    name="status"
                                    control={control}
                                    render={({ field }) => (
                                        <NativeSelect
                                            value={field.value}
                                            onValueChange={field.onChange}
                                            options={statusOptions}
                                            placeholder={ph('status')}
                                        />
                                    )}
                                />
                            </FormField>
                        </div>
                        <FormField label={t('fields.description')}>
                            <Textarea {...register('description')} placeholder={ph('description')} rows={2} />
                        </FormField>
                        <FormField label={t('fields.instructions')} hint={t('placeholders.instructions_hint')}>
                            <Textarea {...register('instructions')} placeholder={ph('instructions')} rows={5} />
                        </FormField>
                    </div>
                )}
                {step === 1 && (
                    <div className="space-y-5">
                        <div className="grid gap-5 lg:grid-cols-2">
                            <FormField label={t('fields.display_name')}>
                                <Input
                                    {...register('persona_display_name')}
                                    list="agent-persona-names"
                                    placeholder={ph('display_name')}
                                />
                                <datalist id="agent-persona-names">
                                    {(genderValue === 'female'
                                        ? personaPresets.name_suggestions?.female
                                        : genderValue === 'male'
                                          ? personaPresets.name_suggestions?.male
                                          : [
                                                ...(personaPresets.name_suggestions?.female ?? []),
                                                ...(personaPresets.name_suggestions?.male ?? []),
                                            ]
                                    )?.map((name) => (
                                        <option key={name} value={name} />
                                    ))}
                                </datalist>
                            </FormField>
                            <FormField label={t('fields.gender')}>
                                <Controller
                                    name="persona_gender"
                                    control={control}
                                    render={({ field }) => (
                                        <NativeSelect
                                            value={field.value}
                                            onValueChange={field.onChange}
                                            options={[
                                                { value: PERSONA_NONE, label: t('persona.none') },
                                                ...personaPresets.genders.map((value) => ({
                                                    value,
                                                    label: t(`persona.gender_${value}`),
                                                })),
                                            ]}
                                        />
                                    )}
                                />
                            </FormField>
                            <FormField label={t('fields.language')}>
                                <Controller
                                    name="persona_language"
                                    control={control}
                                    render={({ field }) => (
                                        <NativeSelect
                                            value={field.value}
                                            onValueChange={field.onChange}
                                            options={[
                                                { value: PERSONA_NONE, label: t('persona.none') },
                                                ...personaPresets.languages.map((value) => ({
                                                    value,
                                                    label: t(`persona.language_${value}`),
                                                })),
                                            ]}
                                        />
                                    )}
                                />
                            </FormField>
                            <FormField label={t('fields.dialect')}>
                                <Controller
                                    name="persona_dialect"
                                    control={control}
                                    render={({ field }) => (
                                        <NativeSelect
                                            value={field.value}
                                            onValueChange={field.onChange}
                                            options={[
                                                { value: PERSONA_NONE, label: t('persona.none') },
                                                ...personaPresets.dialects.map((value) => ({
                                                    value,
                                                    label: t(`persona.dialect_${value}`),
                                                })),
                                            ]}
                                        />
                                    )}
                                />
                            </FormField>
                            <FormField label={t('fields.tone')}>
                                <Controller
                                    name="persona_tone"
                                    control={control}
                                    render={({ field }) => (
                                        <NativeSelect
                                            value={field.value}
                                            onValueChange={field.onChange}
                                            options={[
                                                { value: PERSONA_NONE, label: t('persona.none') },
                                                ...personaPresets.tones.map((value) => ({
                                                    value,
                                                    label: t(`persona.tone_${value}`),
                                                })),
                                            ]}
                                        />
                                    )}
                                />
                            </FormField>
                        </div>
                        <FormField label={t('fields.persona_notes')}>
                            <Textarea {...register('persona_notes')} placeholder={ph('persona_notes')} rows={2} />
                        </FormField>
                    </div>
                )}
                {step === 2 && (
                    <div className="grid gap-5 lg:grid-cols-2">
                            <FormField label={t('fields.provider')} hint={t('placeholders.provider_select')}>
                                {!registryReady ? (
                                    <Skeleton className="h-11 w-full" />
                                ) : (
                                    <Controller
                                        name="provider"
                                        control={control}
                                        rules={{ required: true }}
                                        render={({ field }) => (
                                            <NativeSelect
                                                value={field.value}
                                                onValueChange={onProviderChange}
                                                options={providerOptions}
                                                placeholder={ph('provider')}
                                            />
                                        )}
                                    />
                                )}
                            </FormField>
                            <FormField label={t('fields.model')} hint={t('placeholders.model_select')}>
                                {!registryReady ? (
                                    <Skeleton className="h-11 w-full" />
                                ) : (
                                    <Controller
                                        name="model"
                                        control={control}
                                        render={({ field }) => (
                                            <NativeSelect
                                                value={field.value}
                                                onValueChange={field.onChange}
                                                options={modelOptions}
                                                placeholder={ph('model')}
                                            />
                                        )}
                                    />
                                )}
                            </FormField>
                    </div>
                )}
                {step === 3 && (
                    <div className="space-y-5">
                        <FormField label={t('fields.skills')}>
                            <Controller
                                name="skills"
                                control={control}
                                render={({ field }) => (
                                    <SlugCheckboxList
                                        apiPath="/skills"
                                        value={field.value}
                                        onChange={field.onChange}
                                        emptyHint={t('forms.empty_skills')}
                                    />
                                )}
                            />
                        </FormField>
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
                    </div>
                )}
                {step === 4 && (
                    <div className="space-y-5">
                        <FormField label={t('fields.permissions')} hint={t('agents.permissions_hint')}>
                            <Input {...register('permissions')} placeholder="orders.*" />
                        </FormField>
                        <div className="grid gap-5 lg:grid-cols-2">
                            <FormField label={t('agents.fields.temperature')}>
                                <Input {...register('temperature')} type="number" step="0.1" placeholder="0.7" />
                            </FormField>
                            <FormField label={t('agents.fields.max_tokens')}>
                                <Input {...register('max_tokens')} type="number" placeholder="2048" />
                            </FormField>
                        </div>
                        <FormField label={t('agents.fields.config')} hint={t('agents.config_hint')}>
                            <Controller
                                name="config_json"
                                control={control}
                                render={({ field }) => <JsonField value={field.value} onChange={field.onChange} rows={6} />}
                            />
                            </FormField>
                    </div>
                )}
            </FormWizard>
        </FormContainer>
    );
}
