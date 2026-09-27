import { useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { CodeBlock } from '../components/docs/CodeBlock';
import { Button } from '../components/ui/Button';
import { Card, CardBody } from '../components/ui/Card';
import { FormField } from '../components/ui/FormField';
import { Input, Textarea } from '../components/ui/Input';
import { NativeSelect } from '../components/ui/NativeSelect';
import { JsonHighlight } from '../components/ui/JsonHighlight';
import { PageContainer } from '../components/ui/PageContainer';
import { PageHeader } from '../components/ui/PageHeader';
import {
    type JsonBuilderKind,
    type SchemaField,
    buildAgentPayload,
    buildCodeToolPayload,
    buildHttpToolPayload,
    buildSkillPayload,
    parseSlugList,
} from '../lib/jsonBuilder';
import { useI18n } from '../lib/i18n';
import { useAdminConfig } from '../lib/config';
import { slugify } from '../lib/slugify';

const KINDS: { value: JsonBuilderKind; labelKey: string }[] = [
    { value: 'http_tool', labelKey: 'docs.json_kind_http' },
    { value: 'code_tool', labelKey: 'docs.json_kind_code' },
    { value: 'skill', labelKey: 'docs.json_kind_skill' },
    { value: 'agent', labelKey: 'docs.json_kind_agent' },
];

const METHODS = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'].map((m) => ({ value: m, label: m }));
const TYPES = ['string', 'integer', 'number', 'boolean', 'array', 'object'].map((t) => ({ value: t, label: t }));

function emptyField(): SchemaField {
    return { name: '', type: 'string', required: false, description: '' };
}

export function JsonBuilderPage() {
    const { t } = useI18n();
    const boot = useAdminConfig();
    const apiBase = boot.apiPrefix;

    const [kind, setKind] = useState<JsonBuilderKind>('http_tool');
    const [name, setName] = useState('Get order');
    const [slug, setSlug] = useState('get-order');
    const [method, setMethod] = useState('GET');
    const [url, setUrl] = useState('https://api.example.com/orders/{order_id}');
    const [handler, setHandler] = useState('myapp.orders.lookup');
    const [instructions, setInstructions] = useState('You help with order lookups.');
    const [provider, setProvider] = useState('ollama');
    const [model, setModel] = useState('qwen3:8b');
    const [toolsList, setToolsList] = useState('get-order');
    const [skillsList, setSkillsList] = useState('orders-ops');
    const [knowledgeList, setKnowledgeList] = useState('kb-orders');
    const [fields, setFields] = useState<SchemaField[]>([
        { name: 'order_id', type: 'string', required: true, description: 'Order id' },
    ]);

    const onNameChange = (value: string) => {
        setName(value);
        if (!slug || slug === slugify(name)) {
            setSlug(slugify(value));
        }
    };

    const payload = useMemo(() => {
        switch (kind) {
            case 'http_tool':
                return buildHttpToolPayload({ name, slug, method, url, fields });
            case 'code_tool':
                return buildCodeToolPayload({ name, slug, handler, fields });
            case 'skill':
                return buildSkillPayload({
                    name,
                    slug,
                    instructions,
                    tools: parseSlugList(toolsList),
                    knowledge: parseSlugList(knowledgeList),
                });
            case 'agent':
                return buildAgentPayload({
                    name,
                    slug,
                    instructions,
                    provider,
                    model,
                    skills: parseSlugList(skillsList),
                    tools: parseSlugList(toolsList),
                    knowledge: parseSlugList(knowledgeList),
                });
            default:
                return {};
        }
    }, [kind, name, slug, method, url, handler, instructions, provider, model, toolsList, skillsList, knowledgeList, fields]);

    const endpoint = useMemo(() => {
        switch (kind) {
            case 'http_tool':
            case 'code_tool':
                return `POST ${apiBase}/tools`;
            case 'skill':
                return `POST ${apiBase}/skills`;
            case 'agent':
                return `POST ${apiBase}/agents`;
            default:
                return apiBase;
        }
    }, [kind, apiBase]);

    const updateField = (index: number, patch: Partial<SchemaField>) => {
        setFields((prev) => prev.map((f, i) => (i === index ? { ...f, ...patch } : f)));
    };

    return (
        <PageContainer>
            <PageHeader
                title={t('docs.json_builder_title')}
                description={t('docs.json_builder_intro')}
                actions={
                    <Link to="/docs">
                        <Button type="button" variant="secondary">{t('docs.back_to_docs')}</Button>
                    </Link>
                }
            />

            <div className="grid gap-6 xl:grid-cols-2">
                <Card>
                    <CardBody className="space-y-4">
                        <FormField label={t('docs.json_kind')}>
                            <NativeSelect
                                value={kind}
                                onValueChange={(v) => setKind(v as JsonBuilderKind)}
                                options={KINDS.map((k) => ({ value: k.value, label: t(k.labelKey) }))}
                            />
                        </FormField>

                        <FormField label={t('fields.name')}>
                            <Input value={name} onChange={(e) => onNameChange(e.target.value)} />
                        </FormField>
                        <FormField label={t('fields.slug')}>
                            <Input value={slug} onChange={(e) => setSlug(e.target.value)} />
                        </FormField>

                        {(kind === 'http_tool' || kind === 'code_tool') && (
                            <>
                                {kind === 'http_tool' && (
                                    <>
                                        <FormField label={t('tools.fields.method')}>
                                            <NativeSelect value={method} onValueChange={setMethod} options={METHODS} />
                                        </FormField>
                                        <FormField label={t('tools.fields.url')}>
                                            <Input value={url} onChange={(e) => setUrl(e.target.value)} />
                                        </FormField>
                                    </>
                                )}
                                {kind === 'code_tool' && (
                                    <FormField label={t('docs.handler_name')} hint={t('docs.handler_hint')}>
                                        <Input value={handler} onChange={(e) => setHandler(e.target.value)} />
                                    </FormField>
                                )}
                                <div className="space-y-2">
                                    <p className="text-sm font-semibold text-slate-800">{t('docs.schema_fields')}</p>
                                    {fields.map((field, index) => (
                                        <div key={index} className="grid gap-2 rounded-lg border border-slate-200 p-3 sm:grid-cols-2">
                                            <Input
                                                placeholder={t('docs.field_name')}
                                                value={field.name}
                                                onChange={(e) => updateField(index, { name: e.target.value })}
                                            />
                                            <NativeSelect
                                                value={field.type}
                                                onValueChange={(v) => updateField(index, { type: v })}
                                                options={TYPES}
                                            />
                                            <Input
                                                placeholder={t('docs.field_description')}
                                                value={field.description ?? ''}
                                                onChange={(e) => updateField(index, { description: e.target.value })}
                                            />
                                            <label className="flex items-center gap-2 text-sm text-slate-700">
                                                <input
                                                    type="checkbox"
                                                    checked={field.required}
                                                    onChange={(e) => updateField(index, { required: e.target.checked })}
                                                />
                                                {t('docs.field_required')}
                                            </label>
                                        </div>
                                    ))}
                                    <Button type="button" variant="secondary" onClick={() => setFields((f) => [...f, emptyField()])}>
                                        {t('docs.add_field')}
                                    </Button>
                                </div>
                            </>
                        )}

                        {(kind === 'agent' || kind === 'skill') && (
                            <>
                                <FormField label={t('fields.instructions')}>
                                    <Textarea value={instructions} onChange={(e) => setInstructions(e.target.value)} rows={4} />
                                </FormField>
                                <FormField label={t('fields.tools')} hint={t('placeholders.list_hint')}>
                                    <Textarea value={toolsList} onChange={(e) => setToolsList(e.target.value)} rows={2} />
                                </FormField>
                                {kind === 'agent' && (
                                    <>
                                        <FormField label={t('fields.skills')} hint={t('placeholders.list_hint')}>
                                            <Textarea value={skillsList} onChange={(e) => setSkillsList(e.target.value)} rows={2} />
                                        </FormField>
                                        <div className="grid gap-4 sm:grid-cols-2">
                                            <FormField label={t('fields.provider')}>
                                                <Input value={provider} onChange={(e) => setProvider(e.target.value)} />
                                            </FormField>
                                            <FormField label={t('fields.model')}>
                                                <Input value={model} onChange={(e) => setModel(e.target.value)} />
                                            </FormField>
                                        </div>
                                    </>
                                )}
                                <FormField label={t('fields.knowledge')} hint={t('placeholders.list_hint')}>
                                    <Textarea value={knowledgeList} onChange={(e) => setKnowledgeList(e.target.value)} rows={2} />
                                </FormField>
                            </>
                        )}
                    </CardBody>
                </Card>

                <div className="space-y-4">
                    <Card>
                        <CardBody className="space-y-3">
                            <h2 className="text-sm font-bold uppercase tracking-wide text-slate-500">{t('docs.json_output')}</h2>
                            <p className="text-xs text-slate-600">{t('docs.json_api_hint', { endpoint })}</p>
                            <JsonHighlight value={payload} />
                        </CardBody>
                    </Card>
                    <CodeBlock
                        title="curl"
                        code={`curl -s -X POST '${window.location.origin}${endpoint}' \\
  -H 'Accept: application/json' \\
  -H 'Content-Type: application/json' \\
  -d '${JSON.stringify(payload)}'`}
                    />
                    <p className="text-sm text-slate-600">{t('docs.json_ui_hint')}</p>
                </div>
            </div>
        </PageContainer>
    );
}
