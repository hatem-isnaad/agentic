import { motion } from 'framer-motion';
import { ArrowUpRight, Copy, Inbox, Plus } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { Link, useNavigate, useSearchParams } from 'react-router-dom';
import { adminApi, type Paginated } from '../lib/api';
import { useAdminConfig } from '../lib/config';
import { statusLabel } from '../lib/hooks';
import { useI18n } from '../lib/i18n';
import { ALL_FILTER, facetOptionsFromRows, matchesFacet, matchesSearch } from '../lib/listFilters';
import { Button } from '../components/ui/Button';
import { DataTable, Table, THead, Th, Td } from '../components/ui/DataTable';
import { PageContainer } from '../components/ui/PageContainer';
import { EmptyState } from '../components/ui/EmptyState';
import { ListFilters } from '../components/ui/ListFilters';
import { PageHeader } from '../components/ui/PageHeader';
import { StatusBadge } from '../components/ui/StatusBadge';
import { TableSkeleton } from '../components/ui/Skeleton';
import type { SelectOption } from '../components/ui/NativeSelect';

type Row = Record<string, unknown>;

type Facet = {
    key: string;
    labelKey: string;
    options?: SelectOption[];
};

type Props = {
    titleKey: string;
    createKey: string;
    emptyKey: string;
    apiPath: string;
    createPath?: string;
    resourceBase: string;
    slugKey?: string;
    columns: { key: string; labelKey: string; type?: 'status' | 'text' }[];
    canClone?: boolean;
    facets?: Facet[];
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
    canClone = false,
    facets,
}: Props) {
    const boot = useAdminConfig();
    const { t } = useI18n();
    const navigate = useNavigate();
    const [searchParams, setSearchParams] = useSearchParams();
    const [rows, setRows] = useState<Row[]>([]);
    const [loading, setLoading] = useState(true);
    const [cloning, setCloning] = useState<string | null>(null);

    const resolvedFacets = useMemo<Facet[]>(() => {
        if (facets && facets.length > 0) {
            return facets;
        }

        return columns
            .filter((col) => ['status', 'driver', 'agent_slug', 'workflow_slug', 'type'].includes(col.key))
            .map((col) => ({ key: col.key, labelKey: col.labelKey }));
    }, [columns, facets]);

    const q = searchParams.get('q') ?? '';

    useEffect(() => {
        adminApi
            .get<Paginated<Row>>(boot, apiPath)
            .then((res) => setRows(res.data))
            .catch(() => setRows([]))
            .finally(() => setLoading(false));
    }, [boot, apiPath]);

    const setParam = (key: string, value: string) => {
        const next = new URLSearchParams(searchParams);
        if (value === '' || value === ALL_FILTER) {
            next.delete(key);
        } else {
            next.set(key, value);
        }
        setSearchParams(next, { replace: true });
    };

    const facetLabel = (key: string, value: string): string => {
        if (key === 'status') {
            return statusLabel(t, value);
        }
        const named = t(`filters.values.${value}`);
        return named.startsWith('filters.values.') ? value : named;
    };

    const filtered = useMemo(() => {
        return rows.filter((row) => {
            if (!matchesSearch(row, q)) {
                return false;
            }

            return resolvedFacets.every((facet) => matchesFacet(row, facet.key, searchParams.get(facet.key) ?? ALL_FILTER));
        });
    }, [q, resolvedFacets, rows, searchParams]);

    const filterControls = resolvedFacets.map((facet) => ({
        id: facet.key,
        label: t(facet.labelKey),
        value: searchParams.get(facet.key) ?? ALL_FILTER,
        onChange: (value: string) => setParam(facet.key, value),
        options:
            facet.options ??
            facetOptionsFromRows(rows, facet.key, (value) => facetLabel(facet.key, value)),
    }));

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

            <DataTable
                toolbar={
                    <ListFilters
                        search={q}
                        onSearch={(value) => setParam('q', value)}
                        searchPlaceholder={t('filters.search')}
                        filters={filterControls}
                        resultCount={filtered.length}
                        totalCount={rows.length}
                        countLabel={t('filters.showing', { shown: String(filtered.length), total: String(rows.length) })}
                        allLabel={t('filters.all')}
                        clearLabel={t('filters.clear')}
                    />
                }
            >
                <Table>
                    <THead>
                        {columns.map((col) => (
                            <Th key={col.key}>{t(col.labelKey)}</Th>
                        ))}
                        <Th className={canClone ? 'w-40' : 'w-24'} />
                    </THead>
                    <tbody>
                        {loading && <TableSkeleton cols={columns.length + 1} />}
                        {!loading && filtered.length === 0 && (
                            <tr>
                                <td colSpan={columns.length + 1}>
                                    <EmptyState
                                        icon={Inbox}
                                        title={rows.length === 0 ? t(emptyKey) : t('filters.empty')}
                                        description={rows.length === 0 ? t('dashboard.intro') : t('filters.empty_hint')}
                                        action={
                                            rows.length === 0 && createPath ? (
                                                <Link to={createPath}>
                                                    <Button type="button" variant="secondary">
                                                        <Plus className="h-4 w-4" />
                                                        {t(createKey)}
                                                    </Button>
                                                </Link>
                                            ) : rows.length > 0 ? (
                                                <Button type="button" variant="secondary" onClick={() => setSearchParams({}, { replace: true })}>
                                                    {t('filters.clear')}
                                                </Button>
                                            ) : undefined
                                        }
                                    />
                                </td>
                            </tr>
                        )}
                        {!loading &&
                            filtered.map((row, index) => {
                                const slug = String(row[slugKey] ?? row.id ?? '');
                                const href = `/${resourceBase}/${slug}`;
                                return (
                                    <motion.tr
                                        key={slug}
                                        initial={{ opacity: 0 }}
                                        animate={{ opacity: 1 }}
                                        transition={{ delay: Math.min(index * 0.02, 0.2) }}
                                        className="cursor-pointer border-b border-slate-100 last:border-0 transition-colors hover:bg-slate-50/90"
                                        onClick={() => navigate(href)}
                                    >
                                        {columns.map((col) => (
                                            <Td key={col.key}>
                                                {col.type === 'status' ? (
                                                    <StatusBadge
                                                        status={String(row[col.key] ?? 'draft')}
                                                        label={statusLabel(t, row[col.key])}
                                                    />
                                                ) : col.key === 'slug' ? (
                                                    <code className="rounded-md bg-slate-100 px-1.5 py-0.5 font-mono text-[12px] text-slate-700">
                                                        {String(row[col.key] ?? '—')}
                                                    </code>
                                                ) : (
                                                    <span className="font-medium text-slate-800">{String(row[col.key] ?? '—')}</span>
                                                )}
                                            </Td>
                                        ))}
                                        <Td className="text-end">
                                            <div className="inline-flex items-center justify-end gap-1">
                                                {canClone && (
                                                    <button
                                                        type="button"
                                                        disabled={cloning === slug}
                                                        onClick={(e) => {
                                                            e.stopPropagation();
                                                            void (async () => {
                                                                setCloning(slug);
                                                                try {
                                                                    const res = await adminApi.post<{ data: { slug: string } }>(
                                                                        boot,
                                                                        `${apiPath}/${slug}/clone`,
                                                                        {},
                                                                    );
                                                                    navigate(`/${resourceBase}/${res.data.slug}/edit`);
                                                                } catch (err) {
                                                                    window.alert(err instanceof Error ? err.message : 'Clone failed');
                                                                } finally {
                                                                    setCloning(null);
                                                                }
                                                            })();
                                                        }}
                                                        className="inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-semibold text-slate-500 hover:bg-slate-100 hover:text-slate-900 disabled:opacity-40"
                                                    >
                                                        <Copy className="h-3.5 w-3.5" />
                                                        {cloning === slug ? t('actions.cloning') : t('actions.clone')}
                                                    </button>
                                                )}
                                                <Link
                                                    to={href}
                                                    onClick={(e) => e.stopPropagation()}
                                                    className="inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-semibold text-slate-500 hover:bg-slate-100 hover:text-slate-900"
                                                >
                                                    {t('actions.view')}
                                                    <ArrowUpRight className="h-3.5 w-3.5" />
                                                </Link>
                                            </div>
                                        </Td>
                                    </motion.tr>
                                );
                            })}
                    </tbody>
                </Table>
            </DataTable>
        </PageContainer>
    );
}
