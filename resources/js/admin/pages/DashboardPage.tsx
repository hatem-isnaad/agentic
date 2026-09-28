import { motion } from 'framer-motion';
import {
    Bot,
    BookOpen,
    GitBranch,
    MessageSquare,
    PanelsTopLeft,
    Wrench,
    Zap,
} from 'lucide-react';
import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { adminApi } from '../lib/api';
import { useAdminConfig } from '../lib/config';
import { useI18n } from '../lib/i18n';
import { Card, CardBody } from '../components/ui/Card';
import { PageContainer } from '../components/ui/PageContainer';
import { PageHeader } from '../components/ui/PageHeader';
import { Skeleton } from '../components/ui/Skeleton';
import { DashboardGuide } from '../components/dashboard/DashboardGuide';
import { DashboardQuickActions } from '../components/dashboard/DashboardQuickActions';
import { useAdminMode } from '../lib/adminMode';

type Stats = Record<string, number>;

const statMeta: Record<string, { icon: typeof Bot; href: string }> = {
    agents: { icon: Bot, href: '/agents' },
    skills: { icon: Zap, href: '/skills' },
    tools: { icon: Wrench, href: '/tools' },
    knowledge_sources: { icon: BookOpen, href: '/knowledge-sources' },
    workflows: { icon: GitBranch, href: '/workflows' },
    executions: { icon: PanelsTopLeft, href: '/executions' },
    conversations: { icon: MessageSquare, href: '/conversations' },
};

export function DashboardPage() {
    const boot = useAdminConfig();
    const { t } = useI18n();
    const { expertMode } = useAdminMode();
    const [stats, setStats] = useState<Stats | null>(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        adminApi
            .get<{ data: { stats: Stats } }>(boot, '/dashboard')
            .then((res) => setStats(res.data.stats))
            .catch(() => setStats({}))
            .finally(() => setLoading(false));
    }, [boot]);

    const items = [
        ['agents', stats?.agents],
        ['skills', stats?.skills],
        ['tools', stats?.tools],
        ['knowledge_sources', stats?.knowledgeSources ?? stats?.knowledge_sources],
        ['workflows', stats?.workflows],
        ['executions', stats?.executions],
        ['conversations', stats?.conversations],
    ] as const;

    return (
        <PageContainer>
            <PageHeader title={t('dashboard.heading')} description={t('dashboard.intro_simple')} />

            <DashboardQuickActions />

            {expertMode && <DashboardGuide />}

            {expertMode && (
                <>
                    <h2 className="mb-3 text-xs font-semibold uppercase tracking-[0.14em] text-slate-400">{t('dashboard.stats_title')}</h2>
                    <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        {items.map(([key, value], i) => {
                            const meta = statMeta[key];
                            const Icon = meta?.icon ?? Bot;
                            return (
                                <motion.div
                                    key={key}
                                    initial={{ opacity: 0, y: 10 }}
                                    animate={{ opacity: 1, y: 0 }}
                                    transition={{ delay: i * 0.04, duration: 0.3 }}
                                >
                                    <Link to={meta?.href ?? '/'} className="block">
                                        <Card className="transition hover:border-slate-300 hover:shadow-[var(--shadow-card)]">
                                            <CardBody className="flex items-center gap-3 py-4">
                                                <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-slate-700">
                                                    <Icon className="h-4 w-4" strokeWidth={1.75} />
                                                </div>
                                                <div className="min-w-0">
                                                    <p className="text-[11px] font-medium text-slate-500">{t(`dashboard.stats.${key}`)}</p>
                                                    {loading ? (
                                                        <Skeleton className="mt-1 h-7 w-12" />
                                                    ) : (
                                                        <p className="text-2xl font-semibold tabular-nums tracking-tight text-slate-950">
                                                            {value ?? 0}
                                                        </p>
                                                    )}
                                                </div>
                                            </CardBody>
                                        </Card>
                                    </Link>
                                </motion.div>
                            );
                        })}
                    </div>
                </>
            )}
        </PageContainer>
    );
}
