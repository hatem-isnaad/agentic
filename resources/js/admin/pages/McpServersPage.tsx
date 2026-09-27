import { RefreshCw } from 'lucide-react';
import { useEffect, useState } from 'react';
import { adminApi } from '../lib/api';
import { useAdminConfig } from '../lib/config';
import { useI18n } from '../lib/i18n';
import { Button } from '../components/ui/Button';
import { Card, CardBody } from '../components/ui/Card';
import { PageContainer } from '../components/ui/PageContainer';
import { PageHeader } from '../components/ui/PageHeader';
import { TableSkeleton } from '../components/ui/Skeleton';
import { EmptyState } from '../components/ui/EmptyState';
import { Server } from 'lucide-react';

export function McpServersPage() {
    const boot = useAdminConfig();
    const { t } = useI18n();
    const [servers, setServers] = useState<string[]>([]);
    const [loading, setLoading] = useState(true);
    const [syncing, setSyncing] = useState<string | null>(null);
    const [lastResult, setLastResult] = useState<Record<string, string[]>>({});
    const [error, setError] = useState<string | null>(null);
    const [catalog, setCatalog] = useState<Record<string, unknown[]>>({});

    const load = () => {
        setLoading(true);
        adminApi
            .get<{ data: string[] }>(boot, '/mcp/servers')
            .then((res) => setServers(res.data))
            .catch(() => setServers([]))
            .finally(() => setLoading(false));
    };

    useEffect(() => {
        load();
    }, [boot]);

    const loadCatalog = async (server: string) => {
        setError(null);
        try {
            const tools = await adminApi.get<{ data: unknown[] }>(boot, `/mcp/servers/${server}/tools`);
            setCatalog((prev) => ({ ...prev, [server]: tools.data }));
        } catch (e) {
            setError(e instanceof Error ? e.message : 'Catalog failed');
        }
    };

    const sync = async (server: string) => {
        setSyncing(server);
        setError(null);
        try {
            const res = await adminApi.post<{ data: { tools: string[] } }>(boot, `/mcp/servers/${server}/sync`, {});
            setLastResult((prev) => ({ ...prev, [server]: res.data.tools }));
        } catch (e) {
            setError(e instanceof Error ? e.message : 'Sync failed');
        } finally {
            setSyncing(null);
        }
    };

    return (
        <PageContainer>
            <PageHeader title={t('mcp.title')} description={t('mcp.intro')} />

            {error && (
                <div className="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{error}</div>
            )}

            <Card className="overflow-hidden">
                <CardBody className="p-0">
                    {loading ? (
                        <div className="p-6">
                            <TableSkeleton rows={3} />
                        </div>
                    ) : servers.length === 0 ? (
                        <EmptyState icon={Server} title={t('mcp.empty')} description={t('mcp.empty_hint')} />
                    ) : (
                        <table className="w-full text-left text-sm">
                            <thead className="border-b border-slate-100 bg-slate-50/80 text-xs font-bold uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th className="px-6 py-3">{t('mcp.fields.server')}</th>
                                    <th className="px-6 py-3">{t('mcp.fields.last_sync')}</th>
                                    <th className="px-6 py-3 text-end">{t('table.actions')}</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {servers.map((server) => (
                                    <tr key={server}>
                                        <td className="px-6 py-4 font-mono font-semibold">{server}</td>
                                        <td className="px-6 py-4 text-slate-600">
                                            {lastResult[server]
                                                ? t('mcp.synced_count', { count: String(lastResult[server].length) })
                                                : '—'}
                                        </td>
                                        <td className="px-6 py-4 text-end">
                                            <div className="flex flex-wrap justify-end gap-2">
                                                <Button type="button" variant="ghost" onClick={() => loadCatalog(server)}>
                                                    {t('mcp.catalog_action')}
                                                </Button>
                                                <Button
                                                    type="button"
                                                    variant="secondary"
                                                    disabled={syncing === server}
                                                    onClick={() => sync(server)}
                                                    className="inline-flex gap-2"
                                                >
                                                    <RefreshCw className={`h-4 w-4 ${syncing === server ? 'animate-spin' : ''}`} />
                                                    {t('mcp.sync_action')}
                                                </Button>
                                            </div>
                                            {catalog[server] && (
                                                <p className="mt-2 text-xs text-slate-500">
                                                    {t('mcp.catalog_count', { count: String(catalog[server].length) })}
                                                </p>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    )}
                </CardBody>
            </Card>
        </PageContainer>
    );
}
