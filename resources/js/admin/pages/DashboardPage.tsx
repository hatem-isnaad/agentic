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

const statMeta: Record<string, { icon: typeof Bot; href: string; color: string }> = {
    agents: { icon: Bot, href: '/agents', color: 'from-blue-500 to-cyan-500' },
    skills: { icon: Zap, href: '/skills', color: 'from-violet-500 to-purple-500' },
    tools: { icon: Wrench, href: '/tools', color: 'from-amber-500 to-orange-500' },
    knowledge_sources: { icon: BookOpen, href: '/knowledge-sources', color: 'from-emerald-500 to-teal-500' },
    workflows: { icon: GitBranch, href: '/workflows', color: 'from-indigo-500 to-blue-500' },
    executions: { icon: PanelsTopLeft, href: '/executions', color: 'from-slate-600 to-slate-800' },
    conversations: { icon: MessageSquare, href: '/conversations', color: 'from-pink-500 to-rose-500' },
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
            <h2 className="mb-4 text-sm font-bold uppercase tracking-wide text-slate-500">{t('dashboard.stats_title')}</h2>
            )}
            {expertMode && (
            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
                {items.map(([key, value], i) => {
                    const meta = statMeta[key];
                    const Icon = meta?.icon ?? Bot;
                    return (
                        <motion.div
                            key={key}
                            initial={{ opacity: 0, y: 16 }}
                            animate={{ opacity: 1, y: 0 }}
                            transition={{ delay: i * 0.05, duration: 0.4, ease: [0.22, 1, 0.36, 1] }}
                        >
                            <Link to={meta?.href ?? '/'} className="block group">
                                <Card className="overflow-hidden transition-all duration-300 group-hover:-translate-y-0.5 group-hover:shadow-[var(--shadow-card)]">
                                    <CardBody className="flex items-start gap-4">
                                        <div
                                            className={`flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br ${meta?.color ?? 'from-brand-500 to-brand-600'} text-white shadow-lg`}
                                        >
                                            <Icon className="h-6 w-6" strokeWidth={2} />
                                        </div>
                                        <div className="min-w-0 flex-1">
                                            <p className="text-xs font-bold uppercase tracking-wide text-slate-500">
                                                {t(`dashboard.stats.${key}`)}
                                            </p>
                                            {loading ? (
                                                <Skeleton className="mt-3 h-9 w-16" />
                                            ) : (
                                                <p className="mt-1 text-3xl font-bold tabular-nums text-slate-900">
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
            )}
        </PageContainer>
    );
}
