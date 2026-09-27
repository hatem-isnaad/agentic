import { ExternalLink, Pencil } from 'lucide-react';
import { useEffect, useState } from 'react';
import { useAdminMode } from '../lib/adminMode';
import { Link, useParams } from 'react-router-dom';
import { adminApi } from '../lib/api';
import { useAdminConfig } from '../lib/config';
import { statusLabel } from '../lib/hooks';
import { useI18n } from '../lib/i18n';
import { Button } from '../components/ui/Button';
import { Card, CardBody } from '../components/ui/Card';
import { FormField } from '../components/ui/FormField';
import { Textarea } from '../components/ui/Input';
import { JsonHighlight } from '../components/ui/JsonHighlight';
import { PageContainer } from '../components/ui/PageContainer';
import { PageHeader } from '../components/ui/PageHeader';
import { Skeleton } from '../components/ui/Skeleton';
import { StatusBadge } from '../components/ui/StatusBadge';
import { AgentConversationsPanel } from '../components/agents/AgentConversationsPanel';

export function AgentDetailPage() {
    const { slug = '' } = useParams();
    const boot = useAdminConfig();
    const { t } = useI18n();
    const [data, setData] = useState<Record<string, unknown> | null>(null);
    const [loading, setLoading] = useState(true);
    const [message, setMessage] = useState('Hello, can you help me?');
    const [runResult, setRunResult] = useState<unknown>(null);
    const [runReply, setRunReply] = useState<string | null>(null);
    const [runError, setRunError] = useState<string | null>(null);
    const [running, setRunning] = useState(false);
    const { expertMode } = useAdminMode();
    const [showTechnical, setShowTechnical] = useState(false);

    const widgetUrl = `${boot.webPrefix.replace(/\/admin\/?$/, '/widget')}?agent=${encodeURIComponent(slug)}`;

    useEffect(() => {
        adminApi
            .get<{ data: Record<string, unknown> }>(boot, `/agents/${slug}`)
            .then((res) => setData(res.data))
            .catch(() => setData(null))
            .finally(() => setLoading(false));
    }, [boot, slug]);

    const execute = async () => {
        setRunning(true);
        setRunError(null);
        setRunResult(null);
        setRunReply(null);
        try {
            const res = await adminApi.post<{ data: Record<string, unknown> }>(boot, `/agents/${slug}/execute`, { message });
            setRunResult(res.data);
            const text = res.data?.text;
            setRunReply(typeof text === 'string' && text.trim() !== '' ? text : null);
        } catch (e) {
            setRunError(e instanceof Error ? e.message : 'Execute failed');
        } finally {
            setRunning(false);
        }
    };

    const title = String(data?.name ?? slug);

    return (
        <PageContainer>
            {loading ? (
                <Skeleton className="h-10 w-72" />
            ) : (
                <PageHeader
                    title={title}
                    description={data?.description ? String(data.description) : undefined}
                    actions={
                        <div className="flex flex-wrap gap-2">
                            <a href={widgetUrl} target="_blank" rel="noreferrer">
                                <Button type="button" variant="secondary">
                                    <ExternalLink className="h-4 w-4" />
                                    {t('agents.open_widget')}
                                </Button>
                            </a>
                            <Link to={`/agents/${slug}/edit`}>
                                <Button type="button" variant="secondary">
                                    <Pencil className="h-4 w-4" />
                                    {t('actions.edit')}
                                </Button>
                            </Link>
                        </div>
                    }
                />
            )}

            <div className="mb-6 flex flex-wrap gap-3">
                {data?.status && <StatusBadge status={String(data.status)} label={statusLabel(t, data.status)} />}
                {data?.provider && (
                    <span className="text-sm text-slate-600">{String(data.provider)} / {String(data.model ?? '')}</span>
                )}
            </div>

            <AgentConversationsPanel agentSlug={slug} />

            <div className="mt-6 grid gap-6 xl:grid-cols-2">
                <Card>
                    <CardBody className="space-y-4">
                        <h2 className="text-sm font-bold uppercase tracking-wide text-slate-500">{t('agents.test_title')}</h2>
                        <FormField label={t('agents.test_message')}>
                            <Textarea value={message} onChange={(e) => setMessage(e.target.value)} rows={4} />
                        </FormField>
                        <Button type="button" disabled={running || !message.trim()} onClick={execute}>
                            {t('agents.test_run')}
                        </Button>
                        {runError && <p className="text-sm text-red-600">{runError}</p>}
                        {runReply && (
                            <div className="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 whitespace-pre-wrap">
                                {runReply}
                            </div>
                        )}
                        {expertMode && runResult !== null && <JsonHighlight value={runResult} />}
                    </CardBody>
                </Card>
                {(expertMode || showTechnical) && (
                <Card>
                    <CardBody>
                        {!expertMode && (
                            <button
                                type="button"
                                className="mb-3 text-sm font-semibold text-brand-700 hover:underline"
                                onClick={() => setShowTechnical((v) => !v)}
                            >
                                {showTechnical ? t('simple.hide_technical') : t('simple.show_technical')}
                            </button>
                        )}
                        {expertMode || showTechnical ? (
                            <>
                                <h2 className="mb-3 text-sm font-bold uppercase tracking-wide text-slate-500">{t('detail.payload')}</h2>
                                <JsonHighlight value={data ?? {}} />
                            </>
                        ) : null}
                    </CardBody>
                </Card>
                )}
            </div>
        </PageContainer>
    );
}
