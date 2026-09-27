import { ExternalLink } from 'lucide-react';
import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { adminApi, type Paginated } from '../../lib/api';
import { useAdminConfig } from '../../lib/config';
import { useI18n } from '../../lib/i18n';
import { Button } from '../ui/Button';
import { Card, CardBody } from '../ui/Card';

type Props = { agentSlug: string };

export function AgentConversationsPanel({ agentSlug }: Props) {
    const boot = useAdminConfig();
    const { t } = useI18n();
    const [rows, setRows] = useState<Record<string, unknown>[]>([]);
    const widgetBase = boot.webPrefix.replace(/\/admin\/?$/, '/widget');

    useEffect(() => {
        adminApi
            .get<Paginated<Record<string, unknown>>>(boot, `/conversations?agent=${encodeURIComponent(agentSlug)}&fetch_limit=10`)
            .then((res) => setRows(res.data))
            .catch(() => setRows([]));
    }, [boot, agentSlug]);

    return (
        <Card>
            <CardBody className="space-y-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <h2 className="text-sm font-bold uppercase tracking-wide text-slate-500">{t('conversations.for_agent')}</h2>
                    <div className="flex flex-wrap gap-2">
                        <Link to={`/conversations?agent=${encodeURIComponent(agentSlug)}`}>
                            <Button type="button" variant="secondary">{t('conversations.view_all')}</Button>
                        </Link>
                        <a href={`${widgetBase}?agent=${encodeURIComponent(agentSlug)}`} target="_blank" rel="noreferrer">
                            <Button type="button" variant="secondary">
                                <ExternalLink className="h-4 w-4" />
                                {t('conversations.new_chat')}
                            </Button>
                        </a>
                    </div>
                </div>
                {rows.length === 0 ? (
                    <p className="text-sm text-slate-600">{t('conversations.none_for_agent')}</p>
                ) : (
                    <ul className="space-y-2 text-sm">
                        {rows.map((row) => {
                            const id = String(row.id ?? '');
                            return (
                                <li key={id} className="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-slate-200 px-3 py-2">
                                    <span className="font-mono text-xs text-slate-600">{id.slice(0, 13)}…</span>
                                    <div className="flex gap-2">
                                        <Link to={`/conversations/${id}`} className="font-semibold text-brand-700 hover:underline">
                                            {t('actions.view')}
                                        </Link>
                                        <a
                                            href={`${widgetBase}?agent=${encodeURIComponent(agentSlug)}&conversation=${encodeURIComponent(id)}`}
                                            target="_blank"
                                            rel="noreferrer"
                                            className="font-semibold text-brand-700 hover:underline"
                                        >
                                            {t('conversations.open_chat')}
                                        </a>
                                    </div>
                                </li>
                            );
                        })}
                    </ul>
                )}
            </CardBody>
        </Card>
    );
}
