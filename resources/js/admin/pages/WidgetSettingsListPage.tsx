import { Pencil } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { adminApi, type Paginated } from '../lib/api';
import { useAdminConfig } from '../lib/config';
import { useI18n } from '../lib/i18n';
import { Button } from '../components/ui/Button';
import { Card, CardBody } from '../components/ui/Card';
import { EmptyState } from '../components/ui/EmptyState';
import { PageContainer } from '../components/ui/PageContainer';
import { PageHeader } from '../components/ui/PageHeader';
import { TableSkeleton } from '../components/ui/Skeleton';
import { Inbox } from 'lucide-react';

type AgentRow = { slug: string; name: string; status?: string };
type OverrideRow = { agent_slug: string };

export function WidgetSettingsListPage() {
    const boot = useAdminConfig();
    const { t } = useI18n();
    const [agents, setAgents] = useState<AgentRow[]>([]);
    const [overrides, setOverrides] = useState<Set<string>>(new Set());
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        Promise.all([
            adminApi.get<Paginated<AgentRow>>(boot, '/agents?per_page=100'),
            adminApi.get<Paginated<OverrideRow>>(boot, '/widget-settings?per_page=100'),
        ])
            .then(([agentRes, widgetRes]) => {
                setAgents(agentRes.data);
                setOverrides(new Set(widgetRes.data.map((r) => r.agent_slug)));
            })
            .catch(() => {
                setAgents([]);
                setOverrides(new Set());
            })
            .finally(() => setLoading(false));
    }, [boot]);

    const sorted = useMemo(
        () => [...agents].sort((a, b) => a.name.localeCompare(b.name)),
        [agents],
    );

    return (
        <PageContainer>
            <PageHeader title={t('widget_settings.title')} description={t('widget_settings.intro')} />

            <Card className="overflow-hidden">
                <CardBody className="p-0">
                    {loading ? (
                        <div className="p-6">
                            <TableSkeleton rows={4} />
                        </div>
                    ) : sorted.length === 0 ? (
                        <EmptyState
                            icon={Inbox}
                            title={t('empty.agents')}
                            description={t('widget_settings.no_agents_hint')}
                            action={
                                <Link to="/agents/new">
                                    <Button type="button">{t('agents.create')}</Button>
                                </Link>
                            }
                        />
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-sm">
                                <thead className="border-b border-slate-100 bg-slate-50/80 text-xs font-bold uppercase tracking-wide text-slate-500">
                                    <tr>
                                        <th className="px-6 py-3">{t('table.agent')}</th>
                                        <th className="px-6 py-3">{t('table.status')}</th>
                                        <th className="px-6 py-3 text-end">{t('table.actions')}</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {sorted.map((agent) => {
                                        const custom = overrides.has(agent.slug);
                                        return (
                                            <tr key={agent.slug} className="hover:bg-slate-50/50">
                                                <td className="px-6 py-4">
                                                    <div className="font-semibold text-slate-900">{agent.name}</div>
                                                    <code className="text-xs text-slate-500">{agent.slug}</code>
                                                </td>
                                                <td className="px-6 py-4">
                                                    <span
                                                        className={
                                                            custom
                                                                ? 'inline-flex rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-800'
                                                                : 'inline-flex rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-600'
                                                        }
                                                    >
                                                        {custom ? t('widget_settings.configured') : t('widget_settings.default')}
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4 text-end">
                                                    <Link to={`/widget-settings/${agent.slug}/edit`}>
                                                        <Button type="button" variant="secondary" className="inline-flex gap-2">
                                                            <Pencil className="h-3.5 w-3.5" />
                                                            {t('actions.edit')}
                                                        </Button>
                                                    </Link>
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

            {sorted.length > 0 && overrides.size === 0 && (
                <p className="mt-4 text-sm text-slate-600">{t('empty.widget_settings')}</p>
            )}
        </PageContainer>
    );
}
