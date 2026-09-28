import { Copy, KeyRound, Plus, Trash2 } from 'lucide-react';
import { useCallback, useEffect, useMemo, useState } from 'react';
import { adminApi, type Paginated } from '../lib/api';
import { useAdminConfig } from '../lib/config';
import { useI18n } from '../lib/i18n';
import { Button } from '../components/ui/Button';
import { Card, CardBody } from '../components/ui/Card';
import { FormField } from '../components/ui/FormField';
import { Input, Textarea } from '../components/ui/Input';
import { PageContainer } from '../components/ui/PageContainer';
import { PageHeader } from '../components/ui/PageHeader';
import { TableSkeleton } from '../components/ui/Skeleton';
import { DataTable } from '../components/ui/DataTable';
import { ListFilters } from '../components/ui/ListFilters';
import { ALL_FILTER, matchesFacet, matchesSearch } from '../lib/listFilters';

type TokenRow = {
    id: number;
    name: string;
    token_prefix: string;
    allowed_agents: string[] | null;
    allowed_origins: string[] | null;
    guest_allowed: boolean;
    sanctum_allowed: boolean;
    enabled: boolean;
    expires_at: string | null;
    last_used_at: string | null;
};

type CreateResponse = TokenRow & { plain_token: string };

export function WidgetEmbedTokensPage() {
    const boot = useAdminConfig();
    const { t } = useI18n();
    const [rows, setRows] = useState<TokenRow[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [showCreate, setShowCreate] = useState(false);
    const [plainOnce, setPlainOnce] = useState<string | null>(null);

    const [name, setName] = useState('');
    const [agents, setAgents] = useState('');
    const [origins, setOrigins] = useState('');
    const [guestAllowed, setGuestAllowed] = useState(true);
    const [sanctumAllowed, setSanctumAllowed] = useState(true);
    const [q, setQ] = useState('');
    const [enabledFilter, setEnabledFilter] = useState(ALL_FILTER);

    const load = useCallback(async () => {
        setLoading(true);
        try {
            const res = await adminApi.get<Paginated<TokenRow>>(boot, '/widget-embed-tokens?per_page=100');
            setRows(res.data);
        } catch {
            setRows([]);
        } finally {
            setLoading(false);
        }
    }, [boot]);

    useEffect(() => {
        void load();
    }, [load]);

    const splitCsv = (s: string): string[] | null => {
        const parts = s.split(/[\n,]/).map((x) => x.trim()).filter(Boolean);

        return parts.length ? parts : null;
    };

    const create = async () => {
        setError(null);
        try {
            const res = await adminApi.post<{ data: CreateResponse }>(boot, '/widget-embed-tokens', {
                name: name.trim() || 'embed',
                allowed_agents: splitCsv(agents),
                allowed_origins: splitCsv(origins),
                guest_allowed: guestAllowed,
                sanctum_allowed: sanctumAllowed,
            });
            setPlainOnce(res.data.plain_token);
            setShowCreate(false);
            setName('');
            setAgents('');
            setOrigins('');
            await load();
        } catch (e) {
            setError(e instanceof Error ? e.message : 'Create failed');
        }
    };

    const revoke = async (id: number) => {
        if (!window.confirm(t('actions.confirm_delete'))) {
            return;
        }
        await adminApi.delete(boot, `/widget-embed-tokens/${id}`);
        await load();
    };

    const toggleEnabled = async (row: TokenRow) => {
        await adminApi.put(boot, `/widget-embed-tokens/${row.id}`, { enabled: !row.enabled });
        await load();
    };

    const visible = useMemo(
        () =>
            rows.filter(
                (row) =>
                    matchesSearch(row, q) &&
                    matchesFacet({ enabled: row.enabled ? 'enabled' : 'disabled' }, 'enabled', enabledFilter),
            ),
        [enabledFilter, q, rows],
    );

    const copyPlain = async () => {
        if (plainOnce) {
            await navigator.clipboard.writeText(plainOnce);
        }
    };

    return (
        <PageContainer>
            <PageHeader
                title={t('embed_tokens.title')}
                description={t('embed_tokens.intro')}
                actions={
                    <Button type="button" onClick={() => setShowCreate(true)}>
                        <Plus className="h-4 w-4" />
                        {t('embed_tokens.create')}
                    </Button>
                }
            />

            {plainOnce && (
                <Card className="mb-6 border-amber-200 bg-amber-50/80">
                    <CardBody className="space-y-3 text-sm">
                        <p className="font-semibold text-amber-900">{t('embed_tokens.plain_once_title')}</p>
                        <p className="text-amber-800">{t('embed_tokens.plain_once_body')}</p>
                        <code className="block break-all rounded-lg bg-slate-900 px-3 py-2 font-mono text-xs text-slate-100">
                            {plainOnce}
                        </code>
                        <div className="flex gap-2">
                            <Button type="button" variant="secondary" onClick={() => void copyPlain()}>
                                <Copy className="h-4 w-4" />
                                {t('embed_tokens.copy')}
                            </Button>
                            <Button type="button" variant="ghost" onClick={() => setPlainOnce(null)}>
                                {t('embed_tokens.dismiss')}
                            </Button>
                        </div>
                    </CardBody>
                </Card>
            )}

            {showCreate && (
                <Card className="mb-6">
                    <CardBody className="space-y-4">
                        <h2 className="text-sm font-bold text-slate-900">{t('embed_tokens.create')}</h2>
                        {error && <p className="text-sm text-red-600">{error}</p>}
                        <FormField label={t('embed_tokens.name')}>
                            <Input value={name} onChange={(e) => setName(e.target.value)} placeholder="production" />
                        </FormField>
                        <FormField label={t('embed_tokens.agents')} hint={t('embed_tokens.agents_hint')}>
                            <Input value={agents} onChange={(e) => setAgents(e.target.value)} placeholder="support, sales" />
                        </FormField>
                        <FormField label={t('embed_tokens.origins')} hint={t('embed_tokens.origins_hint')}>
                            <Textarea value={origins} onChange={(e) => setOrigins(e.target.value)} rows={2} placeholder="https://app.example.com" />
                        </FormField>
                        <label className="flex items-center gap-2 text-sm">
                            <input type="checkbox" checked={guestAllowed} onChange={(e) => setGuestAllowed(e.target.checked)} />
                            {t('embed_tokens.guest_allowed')}
                        </label>
                        <label className="flex items-center gap-2 text-sm">
                            <input type="checkbox" checked={sanctumAllowed} onChange={(e) => setSanctumAllowed(e.target.checked)} />
                            {t('embed_tokens.sanctum_allowed')}
                        </label>
                        <div className="flex gap-2">
                            <Button type="button" onClick={() => void create()}>{t('actions.save')}</Button>
                            <Button type="button" variant="ghost" onClick={() => setShowCreate(false)}>{t('actions.cancel')}</Button>
                        </div>
                    </CardBody>
                </Card>
            )}

            <DataTable
                toolbar={
                    <ListFilters
                        search={q}
                        onSearch={setQ}
                        searchPlaceholder={t('filters.search')}
                        filters={[
                            {
                                id: 'enabled',
                                label: t('filters.enabled'),
                                value: enabledFilter,
                                onChange: setEnabledFilter,
                                options: [
                                    { value: 'enabled', label: t('filters.values.enabled') },
                                    { value: 'disabled', label: t('filters.values.disabled') },
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
                        <div className="p-6"><TableSkeleton rows={3} /></div>
                    ) : visible.length === 0 ? (
                        <div className="flex flex-col items-center gap-3 p-12 text-center text-sm text-slate-600">
                            <KeyRound className="h-10 w-10 text-slate-300" />
                            <p>{t('embed_tokens.empty')}</p>
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-sm">
                                <thead className="border-b border-slate-100 bg-slate-50/80 text-xs font-bold uppercase tracking-wide text-slate-500">
                                    <tr>
                                        <th className="px-4 py-3">{t('embed_tokens.name')}</th>
                                        <th className="px-4 py-3">{t('embed_tokens.prefix')}</th>
                                        <th className="px-4 py-3">{t('embed_tokens.agents')}</th>
                                        <th className="px-4 py-3">{t('table.status')}</th>
                                        <th className="px-4 py-3 text-end">{t('table.actions')}</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {visible.map((row) => (
                                        <tr key={row.id} className="hover:bg-slate-50/50">
                                            <td className="px-4 py-3 font-medium">{row.name}</td>
                                            <td className="px-4 py-3 font-mono text-xs">{row.token_prefix}…</td>
                                            <td className="px-4 py-3 text-xs text-slate-600">
                                                {row.allowed_agents?.join(', ') || '*'}
                                            </td>
                                            <td className="px-4 py-3">
                                                <button
                                                    type="button"
                                                    className={`rounded-full px-2 py-0.5 text-xs font-semibold ${row.enabled ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-500'}`}
                                                    onClick={() => void toggleEnabled(row)}
                                                >
                                                    {row.enabled ? t('embed_tokens.active') : t('embed_tokens.revoked')}
                                                </button>
                                            </td>
                                            <td className="px-4 py-3 text-end">
                                                <Button type="button" variant="ghost" className="text-red-600" onClick={() => void revoke(row.id)}>
                                                    <Trash2 className="h-4 w-4" />
                                                </Button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
            </DataTable>

            <p className="mt-4 text-xs text-slate-500">{t('embed_tokens.env_hint')}</p>
        </PageContainer>
    );
}
