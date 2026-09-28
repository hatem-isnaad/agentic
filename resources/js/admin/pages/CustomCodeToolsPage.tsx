import { FolderCode, RefreshCw } from 'lucide-react';
import { useCallback, useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { adminApi } from '../lib/api';
import { useAdminConfig } from '../lib/config';
import { useI18n } from '../lib/i18n';
import { Button } from '../components/ui/Button';
import { HelpCallout } from '../components/ui/HelpCallout';
import { PageContainer } from '../components/ui/PageContainer';
import { PageHeader } from '../components/ui/PageHeader';
import { Skeleton } from '../components/ui/Skeleton';
import { CodeBlock } from '../components/docs/CodeBlock';
import { DataTable } from '../components/ui/DataTable';
import { ListFilters } from '../components/ui/ListFilters';
import { ALL_FILTER, matchesFacet, matchesSearch } from '../lib/listFilters';

type HandlerRow = {
    handler: string;
    class: string;
    description: string;
    registered: boolean;
    tool_slug?: string | null;
    tool_status?: string | null;
};

export function CustomCodeToolsPage() {
    const boot = useAdminConfig();
    const { t } = useI18n();
    const [rows, setRows] = useState<HandlerRow[]>([]);
    const [customPath, setCustomPath] = useState('');
    const [loading, setLoading] = useState(true);
    const [busy, setBusy] = useState(false);
    const [message, setMessage] = useState<string | null>(null);
    const [error, setError] = useState<string | null>(null);
    const [q, setQ] = useState('');
    const [linkedFilter, setLinkedFilter] = useState(ALL_FILTER);

    const load = useCallback(() => {
        setLoading(true);
        adminApi
            .get<{ data: HandlerRow[]; meta?: { custom_path?: string } }>(boot, '/code-handlers')
            .then((res) => {
                setRows(res.data);
                setCustomPath(String(res.meta?.custom_path ?? ''));
            })
            .catch(() => setRows([]))
            .finally(() => setLoading(false));
    }, [boot]);

    useEffect(() => {
        load();
    }, [load]);

    const sync = async () => {
        setBusy(true);
        setError(null);
        setMessage(null);
        try {
            await adminApi.post(boot, '/code-handlers/sync', {});
            setMessage(t('custom_tools.synced'));
            load();
        } catch (e) {
            setError(e instanceof Error ? e.message : 'Sync failed');
        } finally {
            setBusy(false);
        }
    };

    const publish = async (handler: string) => {
        setBusy(true);
        setError(null);
        try {
            const res = await adminApi.post<{ data: { slug: string } }>(boot, '/code-handlers/publish', {
                handler,
                publish: true,
            });
            setMessage(t('custom_tools.published', { slug: res.data.slug }));
            load();
        } catch (e) {
            setError(e instanceof Error ? e.message : 'Publish failed');
        } finally {
            setBusy(false);
        }
    };

    const visible = useMemo(
        () =>
            rows.filter(
                (row) =>
                    matchesSearch(row, q) &&
                    matchesFacet({ linked: row.tool_slug ? 'yes' : 'no' }, 'linked', linkedFilter),
            ),
        [linkedFilter, q, rows],
    );

    const cliMake = 'php artisan agentic:make-code-tool LookupOrder --handler=myapp.orders.lookup';
    const cliSync = 'php artisan agentic:code-tools-sync';

    return (
        <PageContainer>
            <PageHeader
                title={t('custom_tools.title')}
                description={t('custom_tools.subtitle')}
                actions={
                    <Button type="button" variant="secondary" disabled={busy} onClick={sync}>
                        <RefreshCw className="h-4 w-4" />
                        {t('custom_tools.sync')}
                    </Button>
                }
            />

            <HelpCallout>{t('custom_tools.hint')}</HelpCallout>

            {customPath && (
                <p className="mb-4 flex items-center gap-2 text-sm text-slate-600">
                    <FolderCode className="h-4 w-4" />
                    <code className="rounded bg-slate-100 px-2 py-0.5 text-xs">{customPath}</code>
                </p>
            )}

            {message && <div className="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{message}</div>}
            {error && <div className="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{error}</div>}

            <div className="mb-8 grid gap-4 lg:grid-cols-2">
                <CodeBlock title="1. Create class" code={cliMake} />
                <CodeBlock title="2. Register handlers" code={cliSync} />
            </div>

            <DataTable
                toolbar={
                    <ListFilters
                        search={q}
                        onSearch={setQ}
                        searchPlaceholder={t('filters.search')}
                        filters={[
                            {
                                id: 'linked',
                                label: t('filters.linked'),
                                value: linkedFilter,
                                onChange: setLinkedFilter,
                                options: [
                                    { value: 'yes', label: t('filters.values.yes') },
                                    { value: 'no', label: t('filters.values.no') },
                                ],
                            },
                        ]}
                        resultCount={visible.length}
                        totalCount={rows.length}
                        countLabel={t('filters.showing', { shown: String(visible.length), total: String(rows.length) })}
                        allLabel={t('filters.all')}
                        clearLabel={t('filters.clear')}
                    />
                }
            >
                    {loading ? (
                        <div className="p-6"><Skeleton className="h-40 w-full" /></div>
                    ) : visible.length === 0 ? (
                        <p className="p-6 text-sm text-slate-600">{rows.length === 0 ? t('custom_tools.empty') : t('filters.empty')}</p>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-sm">
                                <thead>
                                    <tr className="border-b border-slate-200 text-xs uppercase text-slate-500">
                                        <th className="py-2 pr-4">{t('custom_tools.col_handler')}</th>
                                        <th className="py-2 pr-4">{t('custom_tools.col_class')}</th>
                                        <th className="py-2 pr-4">{t('custom_tools.col_tool')}</th>
                                        <th className="py-2">{t('custom_tools.col_actions')}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {visible.map((row) => (
                                        <tr key={row.handler} className="border-b border-slate-100">
                                            <td className="py-3 pr-4 font-mono text-xs">{row.handler}</td>
                                            <td className="py-3 pr-4 text-slate-700">{row.class.split('\\').pop()}</td>
                                            <td className="py-3 pr-4">
                                                {row.tool_slug ? (
                                                    <Link to={`/tools/${row.tool_slug}`} className="text-brand-700 hover:underline">
                                                        {row.tool_slug}
                                                    </Link>
                                                ) : (
                                                    <span className="text-slate-400">{t('custom_tools.not_linked')}</span>
                                                )}
                                            </td>
                                            <td className="py-3">
                                                {!row.tool_slug && (
                                                    <Button type="button" variant="secondary" disabled={busy} onClick={() => publish(row.handler)}>
                                                        {t('custom_tools.publish')}
                                                    </Button>
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
            </DataTable>
        </PageContainer>
    );
}
