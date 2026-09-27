import { CheckCircle2, ChevronLeft, ChevronRight, Sparkles } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { adminApi } from '../lib/api';
import { useAdminConfig } from '../lib/config';
import { useI18n } from '../lib/i18n';
import { slugify } from '../lib/slugify';
import { Button } from '../components/ui/Button';
import { Card, CardBody } from '../components/ui/Card';
import { FormField } from '../components/ui/FormField';
import { Input, Textarea } from '../components/ui/Input';
import { NativeSelect } from '../components/ui/NativeSelect';
import { HelpCallout } from '../components/ui/HelpCallout';
import { PageContainer } from '../components/ui/PageContainer';
import { PageHeader } from '../components/ui/PageHeader';

type RegistryProvider = { key: string; label: string; models: string[] };

const STEPS = ['intro', 'agent', 'extras', 'done'] as const;
type Step = (typeof STEPS)[number];

export function SetupWizardPage() {
    const boot = useAdminConfig();
    const { t } = useI18n();
    const [step, setStep] = useState<Step>('intro');
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const [agentName, setAgentName] = useState('My assistant');
    const [instructions, setInstructions] = useState('You are a helpful assistant. Be concise and accurate.');
    const [provider, setProvider] = useState('');
    const [model, setModel] = useState('');
    const [registry, setRegistry] = useState<RegistryProvider[]>([]);

    const [addTool, setAddTool] = useState(false);
    const [toolName, setToolName] = useState('Look up order');
    const [toolUrl, setToolUrl] = useState('https://api.example.com/orders/{order_id}');

    const [addKnowledge, setAddKnowledge] = useState(false);
    const [knowledgeText, setKnowledgeText] = useState('Our return policy allows 30 days from delivery.');

    const [createdAgentSlug, setCreatedAgentSlug] = useState<string | null>(null);
    const [testMessage, setTestMessage] = useState('Hello!');
    const [testReply, setTestReply] = useState<string | null>(null);

    const agentSlug = useMemo(() => slugify(agentName), [agentName]);
    const skillSlug = useMemo(() => `${agentSlug}-skill`, [agentSlug]);
    const toolSlug = useMemo(() => slugify(toolName), [toolName]);
    const kbSlug = useMemo(() => `kb-${agentSlug}`, [agentSlug]);

    useEffect(() => {
        adminApi
            .get<{
                data: {
                    providers: RegistryProvider[];
                    default_provider: string | null;
                    default_model: string | null;
                };
            }>(boot, '/ai-registry')
            .then((res) => {
                setRegistry(res.data.providers);
                const defaultKey =
                    res.data.providers.find((p) => p.key === res.data.default_provider)?.key ??
                    res.data.providers[0]?.key ??
                    '';
                const models = res.data.providers.find((p) => p.key === defaultKey)?.models ?? [];
                const defaultModel =
                    (res.data.default_model && models.includes(res.data.default_model)
                        ? res.data.default_model
                        : models[0]) ?? '';
                setProvider(defaultKey);
                setModel(defaultModel);
            })
            .catch(() => setRegistry([]));
    }, [boot]);

    const providerOptions = registry.map((p) => ({ value: p.key, label: p.label }));
    const modelOptions = (registry.find((p) => p.key === provider)?.models ?? []).map((m) => ({
        value: m,
        label: m,
    }));

    const stepIndex = STEPS.indexOf(step);

    const goNext = () => {
        const next = STEPS[stepIndex + 1];
        if (next) setStep(next);
    };

    const goBack = () => {
        const prev = STEPS[stepIndex - 1];
        if (prev) setStep(prev);
    };

    const createAll = async () => {
        setBusy(true);
        setError(null);
        try {
            const toolSlugs: string[] = [];
            const knowledgeSlugs: string[] = [];

            if (addTool) {
                await adminApi.post(boot, '/tools', {
                    name: toolName,
                    slug: toolSlug,
                    driver: 'http',
                    status: 'published',
                    publish: true,
                    definition: {
                        method: 'GET',
                        url: toolUrl,
                        input_schema: {
                            type: 'object',
                            properties: { order_id: { type: 'string' } },
                            required: ['order_id'],
                        },
                    },
                });
                toolSlugs.push(toolSlug);
            }

            if (addKnowledge) {
                await adminApi.post(boot, '/knowledge-sources', {
                    name: `${agentName} knowledge`,
                    slug: kbSlug,
                    driver: 'vector',
                    status: 'published',
                    config: {},
                });
                await adminApi.post(boot, `/knowledge-sources/${kbSlug}/ingest`, {
                    format: 'text',
                    raw_text: knowledgeText,
                    reindex: true,
                });
                knowledgeSlugs.push(kbSlug);
            }

            await adminApi.post(boot, '/skills', {
                name: `${agentName} skill`,
                slug: skillSlug,
                status: 'published',
                instructions,
                tools: toolSlugs,
                knowledge: knowledgeSlugs,
            });

            await adminApi.post(boot, '/agents', {
                name: agentName,
                slug: agentSlug,
                status: 'published',
                instructions,
                provider,
                model,
                skills: [skillSlug],
                tools: toolSlugs,
                knowledge: knowledgeSlugs,
            });

            setCreatedAgentSlug(agentSlug);
            setStep('done');
        } catch (e) {
            setError(e instanceof Error ? e.message : 'Setup failed');
        } finally {
            setBusy(false);
        }
    };

    const runTest = async () => {
        if (!createdAgentSlug) return;
        setBusy(true);
        setError(null);
        try {
            const res = await adminApi.post<{ data: { text?: string } }>(
                boot,
                `/agents/${createdAgentSlug}/execute`,
                { message: testMessage },
            );
            setTestReply(typeof res.data?.text === 'string' ? res.data.text : JSON.stringify(res.data));
        } catch (e) {
            setError(e instanceof Error ? e.message : 'Test failed');
        } finally {
            setBusy(false);
        }
    };

    const widgetUrl = `${boot.webPrefix.replace(/\/admin\/?$/, '/widget')}?agent=${encodeURIComponent(createdAgentSlug ?? agentSlug)}`;

    return (
        <PageContainer>
            <PageHeader title={t('setup.title')} description={t('setup.subtitle')} />

            <div className="mb-6 flex gap-2">
                {STEPS.map((s, i) => (
                    <div
                        key={s}
                        className={`h-2 flex-1 rounded-full ${i <= stepIndex ? 'bg-brand-500' : 'bg-slate-200'}`}
                    />
                ))}
            </div>

            {error && (
                <div className="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{error}</div>
            )}

            {step === 'intro' && (
                <Card>
                    <CardBody className="space-y-6 text-center py-10">
                        <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-brand-100 text-brand-700">
                            <Sparkles className="h-8 w-8" />
                        </div>
                        <div className="mx-auto max-w-lg space-y-2">
                            <h2 className="text-xl font-bold text-slate-900">{t('setup.intro_heading')}</h2>
                            <p className="text-sm text-slate-600">{t('setup.intro_body')}</p>
                        </div>
                        <Button type="button" onClick={goNext}>{t('setup.start')}</Button>
                    </CardBody>
                </Card>
            )}

            {step === 'agent' && (
                <Card>
                    <CardBody className="space-y-4">
                        <h2 className="text-lg font-bold text-slate-900">{t('setup.step_agent')}</h2>
                        <HelpCallout>{t('setup.agent_hint')}</HelpCallout>
                        <FormField label={t('fields.name')}>
                            <Input value={agentName} onChange={(e) => setAgentName(e.target.value)} />
                        </FormField>
                        <FormField label={t('fields.instructions')}>
                            <Textarea value={instructions} onChange={(e) => setInstructions(e.target.value)} rows={4} />
                        </FormField>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <FormField label={t('fields.provider')}>
                                <NativeSelect
                                    value={provider}
                                    onValueChange={(v) => {
                                        setProvider(v);
                                        const models = registry.find((p) => p.key === v)?.models ?? [];
                                        setModel(models[0] ?? '');
                                    }}
                                    options={providerOptions}
                                />
                            </FormField>
                            <FormField label={t('fields.model')}>
                                <NativeSelect value={model} onValueChange={setModel} options={modelOptions} />
                            </FormField>
                        </div>
                        <div className="flex justify-between pt-2">
                            <Button type="button" variant="secondary" onClick={goBack}>
                                <ChevronLeft className="h-4 w-4" /> {t('actions.back')}
                            </Button>
                            <Button type="button" onClick={goNext}>
                                {t('setup.next')} <ChevronRight className="h-4 w-4" />
                            </Button>
                        </div>
                    </CardBody>
                </Card>
            )}

            {step === 'extras' && (
                <Card>
                    <CardBody className="space-y-6">
                        <h2 className="text-lg font-bold text-slate-900">{t('setup.step_extras')}</h2>
                        <p className="text-sm text-slate-600">{t('setup.extras_intro')}</p>

                        <label className="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-4">
                            <input type="checkbox" checked={addTool} onChange={(e) => setAddTool(e.target.checked)} className="mt-1" />
                            <div className="space-y-2 flex-1">
                                <span className="font-semibold text-slate-900">{t('setup.add_api_tool')}</span>
                                <p className="text-xs text-slate-600">{t('setup.add_api_tool_hint')}</p>
                                {addTool && (
                                    <div className="grid gap-3 pt-2">
                                        <Input value={toolName} onChange={(e) => setToolName(e.target.value)} placeholder={t('fields.name')} />
                                        <Input value={toolUrl} onChange={(e) => setToolUrl(e.target.value)} placeholder="https://..." />
                                    </div>
                                )}
                            </div>
                        </label>

                        <label className="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-4">
                            <input
                                type="checkbox"
                                checked={addKnowledge}
                                onChange={(e) => setAddKnowledge(e.target.checked)}
                                className="mt-1"
                            />
                            <div className="space-y-2 flex-1">
                                <span className="font-semibold text-slate-900">{t('setup.add_knowledge')}</span>
                                <p className="text-xs text-slate-600">{t('setup.add_knowledge_hint')}</p>
                                {addKnowledge && (
                                    <Textarea value={knowledgeText} onChange={(e) => setKnowledgeText(e.target.value)} rows={4} />
                                )}
                            </div>
                        </label>

                        <div className="flex justify-between">
                            <Button type="button" variant="secondary" onClick={goBack}>
                                <ChevronLeft className="h-4 w-4" /> {t('actions.back')}
                            </Button>
                            <Button type="button" disabled={busy} onClick={createAll}>
                                {t('setup.create')}
                            </Button>
                        </div>
                    </CardBody>
                </Card>
            )}

            {step === 'done' && createdAgentSlug && (
                <Card>
                    <CardBody className="space-y-6">
                        <div className="flex items-center gap-3 text-emerald-800">
                            <CheckCircle2 className="h-8 w-8" />
                            <div>
                                <h2 className="text-lg font-bold">{t('setup.done_heading')}</h2>
                                <p className="text-sm">{t('setup.done_body', { name: agentName })}</p>
                            </div>
                        </div>
                        <FormField label={t('agents.test_message')}>
                            <Textarea value={testMessage} onChange={(e) => setTestMessage(e.target.value)} rows={2} />
                        </FormField>
                        <div className="flex flex-wrap gap-2">
                            <Button type="button" disabled={busy} onClick={runTest}>{t('agents.test_run')}</Button>
                            <a href={widgetUrl} target="_blank" rel="noreferrer">
                                <Button type="button" variant="secondary">{t('agents.open_widget')}</Button>
                            </a>
                            <Link to={`/agents/${createdAgentSlug}`}>
                                <Button type="button" variant="secondary">{t('setup.open_agent')}</Button>
                            </Link>
                        </div>
                        {testReply && (
                            <div className="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm whitespace-pre-wrap text-slate-800">
                                {testReply}
                            </div>
                        )}
                    </CardBody>
                </Card>
            )}
        </PageContainer>
    );
}
