import { ExternalLink, MessageSquare } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { adminApi, type Paginated } from '../lib/api';
import { useAdminConfig } from '../lib/config';
import { useI18n } from '../lib/i18n';
import { Button } from '../components/ui/Button';
import { Card, CardBody } from '../components/ui/Card';
import { FormField } from '../components/ui/FormField';
import { NativeSelect } from '../components/ui/NativeSelect';
import { PageContainer } from '../components/ui/PageContainer';
import { PageHeader } from '../components/ui/PageHeader';
import { TableSkeleton } from '../components/ui/Skeleton';
import { EmptyState } from '../components/ui/EmptyState';

type Row = Record<string, unknown>;

export function ConversationsPage() {
    const boot = useAdminConfig();
    const { t } = useI18n();
    const [searchParams, setSearchParams] = useSearchParams();
    const agentFilter = searchParams.get('agent') ?? '';

    const [agents, setAgents] = useState<{ slug: string; name: string }[]>([]);
    const [rows, setRows] = useState<Row[]>([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        adminApi
            .get<Paginated<Row>>(boot, '/agents')
            .then((res) =>
                setAgents(
                    res.data.map((a) => ({
                        slug: String(a.slug ?? ''),
                        name: String(a.name ?? a.slug ?? ''),
                    })),
                ),
            )
            .catch(() => setAgents([]));
    }, [boot]);

    useEffect(() => {
        setLoading(true);
        const path = agentFilter ? `/conversations?agent=${encodeURIComponent(agentFilter)}` : '/conversations';
        adminApi
            .get<Paginated<Row>>(boot, path)
            .then((res) => setRows(res.data))
            .catch(() => setRows([]))
            .finally(() => setLoading(false));
    }, [boot, agentFilter]);

    const widgetBase = boot.webPrefix.replace(/\/admin\/?$/, '/widget');

    const agentOptions = useMemo(
        () => [
            { value: '', label: t('conversations.all_agents') },
            ...agents.map((a) => ({ value: a.slug, label: a.name })),
        ],
        [agents, t],
    );

    return (
        <PageContainer>
            <PageHeader title={t('conversations.title')} description={t('conversations.list_intro')} />

            <Card className="mb-6">
                <CardBody className="flex flex-wrap items-end gap-4">
                    <div className="min-w-[220px] flex-1">
                    <FormField label={t('fields.agent')}>
                        <NativeSelect
                            value={agentFilter}
                            onValueChange={(v) => {
                                if (v) {
                                    setSearchParams({ agent: v });
                                } else {
                                    setSearchParams({});
                                }
                            }}
                            options={agentOptions}
                        />
                    </FormField>
                    </div>
                    {agentFilter && (
                        <a href={`${widgetBase}?agent=${encodeURIComponent(agentFilter)}`} target="_blank" rel="noreferrer">
                            <Button type="button" variant="secondary">
                                <ExternalLink className="h-4 w-4" />
                                {t('conversations.new_chat')}
                            </Button>
                        </a>
                    )}
                </CardBody>
            </Card>

            <Card>
                <CardBody>
                    {loading ? (
                        <TableSkeleton />
                    ) : rows.length === 0 ? (
                        <EmptyState icon={MessageSquare} title={t('empty.conversations')} description={t('conversations.list_intro')} />
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-sm">
                                <thead>
                                    <tr className="border-b border-slate-200 text-xs uppercase text-slate-500">
                                        <th className="py-2 pr-4">{t('table.id')}</th>
                                        <th className="py-2 pr-4">{t('fields.agent')}</th>
                                        <th className="py-2 pr-4">{t('fields.user_id')}</th>
                                        <th className="py-2 pr-4">{t('fields.updated_at')}</th>
                                        <th className="py-2">{t('actions.view')}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {rows.map((row) => {
                                        const id = String(row.id ?? '');
                                        const agent = String(row.agent ?? '');
                                        return (
                                            <tr key={id} className="border-b border-slate-100">
                                                <td className="py-3 pr-4 font-mono text-xs">{id.slice(0, 8)}…</td>
                                                <td className="py-3 pr-4">
                                                    <Link to={`/agents/${agent}`} className="text-brand-700 hover:underline">{agent}</Link>
                                                </td>
                                                <td className="py-3 pr-4 text-slate-600">{String(row.user_id ?? '—')}</td>
                                                <td className="py-3 pr-4 text-slate-600">{String(row.updated_at ?? row.created_at ?? '—')}</td>
                                                <td className="py-3">
                                                    <div className="flex flex-wrap gap-2">
                                                        <Link to={`/conversations/${id}`}>
                                                            <Button type="button" variant="secondary">{t('actions.view')}</Button>
                                                        </Link>
                                                        <a
                                                            href={`${widgetBase}?agent=${encodeURIComponent(agent)}&conversation=${encodeURIComponent(id)}`}
                                                            target="_blank"
                                                            rel="noreferrer"
                                                        >
                                                            <Button type="button" variant="ghost">{t('conversations.open_chat')}</Button>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
                    )}
                </CardBody>
            </Card>
        </PageContainer>
    );
}
