import { ExternalLink } from 'lucide-react';
import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { adminApi } from '../lib/api';
import { useAdminConfig } from '../lib/config';
import { useI18n } from '../lib/i18n';
import { Button } from '../components/ui/Button';
import { Card, CardBody } from '../components/ui/Card';
import { FormField } from '../components/ui/FormField';
import { Textarea } from '../components/ui/Input';
import { PageContainer } from '../components/ui/PageContainer';
import { PageHeader } from '../components/ui/PageHeader';
import { Skeleton } from '../components/ui/Skeleton';

type MessageRow = { role?: string; html?: string; content?: string };

function stripHtml(html: string): string {
    const tmp = document.createElement('div');
    tmp.innerHTML = html;
    return tmp.textContent || tmp.innerText || '';
}

export function ConversationDetailPage() {
    const { id = '' } = useParams();
    const boot = useAdminConfig();
    const { t } = useI18n();
    const [data, setData] = useState<Record<string, unknown> | null>(null);
    const [messages, setMessages] = useState<MessageRow[]>([]);
    const [loading, setLoading] = useState(true);
    const [reply, setReply] = useState('');
    const [busy, setBusy] = useState(false);

    const handoff = (data?.metadata as { handoff?: { status?: string; by?: string } } | undefined)?.handoff;
    const status = handoff?.status ?? 'none';

    const agentSlug = String(data?.agent ?? '');
    const widgetUrl =
        agentSlug && id
            ? `${boot.webPrefix.replace(/\/admin\/?$/, '/widget')}?agent=${encodeURIComponent(agentSlug)}&conversation=${encodeURIComponent(id)}`
            : '';

    useEffect(() => {
        setLoading(true);
        Promise.all([
            adminApi.get<{ data: Record<string, unknown> }>(boot, `/conversations/${id}`),
            adminApi.get<{ data: MessageRow[] }>(boot, `/conversations/${id}/messages`),
        ])
            .then(([conv, msgs]) => {
                setData(conv.data);
                setMessages(msgs.data);
            })
            .catch(() => {
                setData(null);
                setMessages([]);
            })
            .finally(() => setLoading(false));
    }, [boot, id]);

    const reload = () => {
        void Promise.all([
            adminApi.get<{ data: Record<string, unknown> }>(boot, `/conversations/${id}`),
            adminApi.get<{ data: MessageRow[] }>(boot, `/conversations/${id}/messages`),
        ]).then(([conv, msgs]) => {
            setData(conv.data);
            setMessages(msgs.data);
        });
    };

    const take = async () => {
        setBusy(true);
        try {
            await adminApi.post(boot, `/conversations/${id}/take`, {});
            reload();
        } finally {
            setBusy(false);
        }
    };

    const release = async () => {
        setBusy(true);
        try {
            await adminApi.post(boot, `/conversations/${id}/release`, {});
            reload();
        } finally {
            setBusy(false);
        }
    };

    const sendReply = async () => {
        if (!reply.trim()) {
            return;
        }
        setBusy(true);
        try {
            await adminApi.post(boot, `/conversations/${id}/reply`, { message: reply.trim() });
            setReply('');
            reload();
        } finally {
            setBusy(false);
        }
    };

    return (
        <PageContainer>
            {loading ? (
                <Skeleton className="h-10 w-72" />
            ) : (
                <PageHeader
                    title={t('conversations.show_title')}
                    description={`#${id.slice(0, 8)}…`}
                    actions={
                        widgetUrl ? (
                            <a href={widgetUrl} target="_blank" rel="noreferrer">
                                <Button type="button">
                                    <ExternalLink className="h-4 w-4" />
                                    {t('conversations.open_chat')}
                                </Button>
                            </a>
                        ) : undefined
                    }
                />
            )}

            <Card className="mb-6">
                <CardBody>
                    <dl className="grid gap-3 text-sm sm:grid-cols-2">
                        <div>
                            <dt className="text-xs font-bold uppercase text-slate-400">{t('fields.agent')}</dt>
                            <dd>
                                {agentSlug ? (
                                    <Link to={`/agents/${agentSlug}`} className="text-brand-700 hover:underline">{agentSlug}</Link>
                                ) : (
                                    '—'
                                )}
                            </dd>
                        </div>
                        <div>
                            <dt className="text-xs font-bold uppercase text-slate-400">{t('fields.user_id')}</dt>
                            <dd>{String(data?.user_id ?? '—')}</dd>
                        </div>
                        <div>
                            <dt className="text-xs font-bold uppercase text-slate-400">{t('inbox.status')}</dt>
                            <dd className="capitalize">{status}{handoff?.by ? ` · ${handoff.by}` : ''}</dd>
                        </div>
                    </dl>
                    <div className="mt-4 flex flex-wrap gap-2">
                        <Button type="button" variant="secondary" disabled={busy} onClick={() => void take()}>
                            {t('inbox.take')}
                        </Button>
                        <Button type="button" variant="ghost" disabled={busy} onClick={() => void release()}>
                            {t('inbox.release')}
                        </Button>
                    </div>
                </CardBody>
            </Card>

            <Card className="mb-6">
                <CardBody className="space-y-3">
                    <FormField label={t('inbox.reply')}>
                        <Textarea value={reply} onChange={(e) => setReply(e.target.value)} rows={3} />
                    </FormField>
                    <Button type="button" disabled={busy || !reply.trim()} onClick={() => void sendReply()}>
                        {t('inbox.send')}
                    </Button>
                </CardBody>
            </Card>

            <Card>
                <CardBody>
                    <h2 className="mb-4 text-sm font-bold uppercase tracking-wide text-slate-500">{t('conversations.messages')}</h2>
                    {messages.length === 0 ? (
                        <p className="text-sm text-slate-600">{t('empty.messages')}</p>
                    ) : (
                        <ul className="space-y-3">
                            {messages.map((msg, i) => {
                                const text =
                                    typeof msg.content === 'string'
                                        ? msg.content
                                        : typeof msg.html === 'string'
                                          ? stripHtml(msg.html)
                                          : '';
                                return (
                                    <li key={i} className="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                                        <span className="text-xs font-bold uppercase text-brand-600">{msg.role ?? 'message'}</span>
                                        <p className="mt-1 whitespace-pre-wrap text-slate-800">{text}</p>
                                    </li>
                                );
                            })}
                        </ul>
                    )}
                </CardBody>
            </Card>
        </PageContainer>
    );
}
