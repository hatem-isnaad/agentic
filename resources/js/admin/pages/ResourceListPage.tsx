import { motion } from 'framer-motion';
import { ArrowRight, Plus, Search } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { adminApi, type Paginated } from '../lib/api';
import { useAdminConfig } from '../lib/config';
import { statusLabel } from '../lib/hooks';
import { useI18n } from '../lib/i18n';
import { Button } from '../components/ui/Button';
import { Card, CardBody } from '../components/ui/Card';
import { PageContainer } from '../components/ui/PageContainer';
import { EmptyState } from '../components/ui/EmptyState';
import { PageHeader } from '../components/ui/PageHeader';
import { StatusBadge } from '../components/ui/StatusBadge';
import { Input } from '../components/ui/Input';
import { TableSkeleton } from '../components/ui/Skeleton';
import { Inbox } from 'lucide-react';

type Row = Record<string, unknown>;

type Props = {
    titleKey: string;
    createKey: string;
    emptyKey: string;
    apiPath: string;
    createPath?: string;
    resourceBase: string;
    slugKey?: string;
    columns: { key: string; labelKey: string; type?: 'status' | 'text' }[];
};

export function ResourceListPage({
    titleKey,
    createKey,
    emptyKey,
    apiPath,
    createPath,
    resourceBase,
    slugKey = 'slug',
    columns,
}: Props) {
    const boot = useAdminConfig();
    const { t } = useI18n();
    const [rows, setRows] = useState<Row[]>([]);
    const [loading, setLoading] = useState(true);
    const [q, setQ] = useState('');

    useEffect(() => {
        adminApi
            .get<Paginated<Row>>(boot, apiPath)
            .then((res) => setRows(res.data))
            .catch(() => setRows([]))
            .finally(() => setLoading(false));
    }, [boot, apiPath]);

    const filtered = useMemo(() => {
        const needle = q.trim().toLowerCase();
        if (!needle) return rows;
        return rows.filter((row) => JSON.stringify(row).toLowerCase().includes(needle));
    }, [rows, q]);

    return (
        <PageContainer>
            <PageHeader
                title={t(titleKey)}
                actions={
                    createPath ? (
                        <Link to={createPath}>
                            <Button type="button">
                                <Plus className="h-4 w-4" />
                                {t(createKey)}
                            </Button>
                        </Link>
                    ) : undefined
                }
            />

            <Card className="overflow-hidden">
                <div className="border-b border-slate-100 px-4 py-3 sm:px-6">
                    <div className="relative max-w-md">
                        <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                        <Input
                            className="pl-10"
                            placeholder={t('actions.search')}
                            value={q}
                            onChange={(e) => setQ(e.target.value)}
                            aria-label="Search"
                        />
                    </div>
                </div>
                <CardBody className="overflow-x-auto p-0">
                    <table className="min-w-full text-sm">
                        <thead>
                            <tr className="border-b border-slate-100 bg-slate-50/80 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500">
                                {columns.map((col) => (
                                    <th key={col.key} className="px-6 py-3.5">{t(col.labelKey)}</th>
                                ))}
                                <th className="px-6 py-3.5 w-24" />
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {loading && <TableSkeleton cols={columns.length + 1} />}
                            {!loading && filtered.length === 0 && (
                                <tr>
                                    <td colSpan={columns.length + 1}>
                                        <EmptyState icon={Inbox} title={t(emptyKey)} description={t('dashboard.intro')} />
                                    </td>
                                </tr>
                            )}
                            {!loading &&
                                filtered.map((row, index) => {
                                    const slug = String(row[slugKey] ?? row.id ?? '');
                                    return (
                                        <motion.tr
                                            key={slug}
                                            initial={{ opacity: 0 }}
                                            animate={{ opacity: 1 }}
                                            transition={{ delay: index * 0.03 }}
                                            className="group transition-colors hover:bg-brand-50/40"
                                        >
                                            {columns.map((col) => (
                                                <td key={col.key} className="px-6 py-4 text-slate-700">
                                                    {col.type === 'status' ? (
                                                        <StatusBadge
                                                            status={String(row[col.key] ?? 'draft')}
                                                            label={statusLabel(t, row[col.key])}
                                                        />
                                                    ) : col.key === 'slug' ? (
                                                        <code className="rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-800">
                                                            {String(row[col.key] ?? '—')}
                                                        </code>
                                                    ) : (
                                                        <span className="font-medium text-slate-800">
                                                            {String(row[col.key] ?? '—')}
                                                        </span>
                                                    )}
                                                </td>
                                            ))}
                                            <td className="px-6 py-4 text-right">
                                                <Link
                                                    to={`/${resourceBase}/${slug}`}
                                                    className="inline-flex items-center gap-1 rounded-lg px-2 py-1 text-sm font-semibold text-brand-600 opacity-0 transition-all group-hover:opacity-100 hover:bg-brand-100/80"
                                                >
                                                    {t('actions.view')}
                                                    <ArrowRight className="h-4 w-4" />
                                                </Link>
                                            </td>
                                        </motion.tr>
                                    );
                                })}
                        </tbody>
                    </table>
                </CardBody>
            </Card>
        </PageContainer>
    );
}
