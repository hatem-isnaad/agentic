import { Pencil, Plus, RefreshCw, Trash2, Link2 } from 'lucide-react';
import { useCallback, useEffect, useMemo, useState } from 'react';
import { adminApi, type Paginated } from '../lib/api';
import { useAdminConfig } from '../lib/config';
import { useI18n } from '../lib/i18n';
import { emptyKv, objectToRows, rowsToObject, type KvRow } from '../lib/httpToolBuilder';
import { FormWizard } from '../components/forms/FormWizard';
import { KeyValueEditor } from '../components/tools/KeyValueEditor';
import { Button } from '../components/ui/Button';
import { FormField } from '../components/ui/FormField';
import { Input } from '../components/ui/Input';
import { NativeSelect } from '../components/ui/NativeSelect';
import { PageContainer } from '../components/ui/PageContainer';
import { PageHeader } from '../components/ui/PageHeader';
import { TableSkeleton } from '../components/ui/Skeleton';
import { HelpCallout } from '../components/ui/HelpCallout';
import { DataTable, Table, THead, Th, Td, Tr, ErrorBanner } from '../components/ui/DataTable';
import { EmptyState } from '../components/ui/EmptyState';
import { ListFilters } from '../components/ui/ListFilters';
import { StatusBadge } from '../components/ui/StatusBadge';
import { ALL_FILTER, facetOptionsFromRows, matchesFacet, matchesSearch } from '../lib/listFilters';

type Config = {
    grant_type?: string | null;
    token_url?: string | null;
    scope?: string | null;
    name?: string | null;
    headers?: Record<string, string>;
    query?: Record<string, string>;
    auth_headers?: Record<string, string>;
    auth_query?: Record<string, string>;
    auth_body?: Record<string, string>;
};

type Row = {
    id: number;
    name: string;
    slug: string;
    type: string;
    status: string;
    has_credentials: boolean;
    credential_keys: string[];
    expires_at: number | null;
    config: Config;
};

const TYPES = [
    { value: 'bearer', label: 'Bearer token' },
    { value: 'oauth2', label: 'OAuth2 (refresh)' },
    { value: 'basic', label: 'Basic' },
    { value: 'header', label: 'Custom header' },
    { value: 'query', label: 'Query key' },
];

function mapFromRows(rows: KvRow[]): Record<string, string> {
    const obj = rowsToObject(rows);
    if (!obj) return {};
    const out: Record<string, string> = {};
    for (const [key, value] of Object.entries(obj)) {
        if (typeof value === 'string' || typeof value === 'number') {
            out[key] = String(value);
        }
    }
    return out;
}

export function ConnectionsPage() {
    const boot = useAdminConfig();
    const { t } = useI18n();
    const [rows, setRows] = useState<Row[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [show, setShow] = useState(false);
    const [editingId, setEditingId] = useState<number | null>(null);
    const [step, setStep] = useState(0);
    const [type, setType] = useState('bearer');
    const [name, setName] = useState('');
    const [slug, setSlug] = useState('');
    const [token, setToken] = useState('');
    const [username, setUsername] = useState('');
    const [password, setPassword] = useState('');
    const [headerName, setHeaderName] = useState('X-Api-Key');
    const [value, setValue] = useState('');
    const [grant, setGrant] = useState('refresh_token');
    const [tokenUrl, setTokenUrl] = useState('');
    const [scope, setScope] = useState('');
    const [clientId, setClientId] = useState('');
    const [clientSecret, setClientSecret] = useState('');
    const [refreshToken, setRefreshToken] = useState('');
    const [headers, setHeaders] = useState<KvRow[]>([emptyKv()]);
    const [query, setQuery] = useState<KvRow[]>([emptyKv()]);
    const [authHeaders, setAuthHeaders] = useState<KvRow[]>([emptyKv()]);
    const [authQuery, setAuthQuery] = useState<KvRow[]>([emptyKv()]);
    const [authBody, setAuthBody] = useState<KvRow[]>([emptyKv()]);
    const [q, setQ] = useState('');
    const [typeFilter, setTypeFilter] = useState(ALL_FILTER);
    const [statusFilter, setStatusFilter] = useState(ALL_FILTER);

    const load = useCallback(async () => {
        setLoading(true);
        try {
            const res = await adminApi.get<Paginated<Row>>(boot, '/connections?per_page=100');
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

    const visible = useMemo(
        () =>
            rows.filter(
                (row) =>
                    matchesSearch(row, q) &&
                    matchesFacet(row as unknown as Record<string, unknown>, 'type', typeFilter) &&
                    matchesFacet(row as unknown as Record<string, unknown>, 'status', statusFilter),
            ),
        [q, rows, statusFilter, typeFilter],
    );

    const resetForm = () => {
        setEditingId(null);
        setStep(0);
        setType('bearer');
        setName('');
        setSlug('');
        setToken('');
        setUsername('');
        setPassword('');
        setHeaderName('X-Api-Key');
        setValue('');
        setGrant('refresh_token');
        setTokenUrl('');
        setScope('');
        setClientId('');
        setClientSecret('');
        setRefreshToken('');
        setHeaders([emptyKv()]);
        setQuery([emptyKv()]);
        setAuthHeaders([emptyKv()]);
        setAuthQuery([emptyKv()]);
        setAuthBody([emptyKv()]);
    };

    const openCreate = () => {
        resetForm();
        setShow(true);
    };

    const openEdit = (row: Row) => {
        resetForm();
        setEditingId(row.id);
        setName(row.name);
        setSlug(row.slug);
        setType(row.type);
        setGrant(row.config.grant_type || 'refresh_token');
        setTokenUrl(row.config.token_url || '');
        setScope(row.config.scope || '');
        setHeaderName(row.config.name || 'X-Api-Key');
        setHeaders(objectToRows(row.config.headers));
        setQuery(objectToRows(row.config.query));
        setAuthHeaders(objectToRows(row.config.auth_headers));
        setAuthQuery(objectToRows(row.config.auth_query));
        setAuthBody(objectToRows(row.config.auth_body));
        setShow(true);
    };

    const save = async () => {
        setError(null);
        const payload = {
            name: name.trim() || slug.trim(),
            slug: slug.trim(),
            type,
            status: 'active',
            token: token || undefined,
            username: username || undefined,
            password: password || undefined,
            name_key: headerName || undefined,
            value: value || undefined,
            grant_type: grant,
            token_url: tokenUrl || undefined,
            scope: scope || undefined,
            client_id: clientId || undefined,
            client_secret: clientSecret || undefined,
            refresh_token: refreshToken || undefined,
            headers: mapFromRows(headers),
            query: mapFromRows(query),
            auth_headers: mapFromRows(authHeaders),
            auth_query: mapFromRows(authQuery),
            auth_body: mapFromRows(authBody),
        };
        try {
            if (editingId) {
                await adminApi.put(boot, `/connections/${editingId}`, payload);
            } else {
                await adminApi.post(boot, '/connections', payload);
            }
            setShow(false);
            resetForm();
            await load();
        } catch (e) {
            setError(e instanceof Error ? e.message : 'Save failed');
        }
    };

    const remove = async (id: number) => {
        if (!window.confirm(t('actions.confirm_delete'))) return;
        await adminApi.delete(boot, `/connections/${id}`);
        await load();
    };

    const refresh = async (id: number) => {
        setError(null);
        try {
            await adminApi.post(boot, `/connections/${id}/refresh`, {});
            await load();
        } catch (e) {
            setError(e instanceof Error ? e.message : 'Refresh failed');
        }
    };

    const canNext = step === 0 ? Boolean(name.trim() && slug.trim()) : true;

    return (
        <PageContainer>
            <PageHeader
                title={t('connections.title')}
                description={t('connections.subtitle')}
                actions={
                    <Button type="button" onClick={openCreate}>
                        <Plus className="h-4 w-4" />
                        {t('connections.create')}
                    </Button>
                }
            />
            <HelpCallout>{t('connections.hint')}</HelpCallout>
            {error && <ErrorBanner message={error} />}

            {show && (
                <div className="mb-8">
                    <FormWizard
                        steps={[
                            { id: 'basics', title: t('wizard.basics'), description: t('wizard.basics_desc') },
                            { id: 'auth', title: t('connections.type'), description: t('connections.hint') },
                            { id: 'extras', title: t('connections.extras'), description: t('connections.extras_desc') },
                        ]}
                        step={step}
                        onStepChange={setStep}
                        onSubmit={() => void save()}
                        canNext={canNext}
                        saveLabel={editingId ? t('actions.update') : t('actions.save')}
                    >
                        {step === 0 && (
                            <div className="grid gap-5 lg:grid-cols-2">
                                <FormField label={t('fields.name')} required>
                                    <Input value={name} onChange={(e) => setName(e.target.value)} />
                                </FormField>
                                <FormField label={t('fields.slug')} required>
                                    <Input value={slug} onChange={(e) => setSlug(e.target.value)} readOnly={Boolean(editingId)} />
                                </FormField>
                                <FormField label={t('connections.type')}>
                                    <NativeSelect value={type} onValueChange={setType} options={TYPES} />
                                </FormField>
                            </div>
                        )}
                        {step === 1 && (
                            <div className="space-y-5">
                                {type === 'bearer' && (
                                    <FormField label={t('connections.token')} hint={t('connections.token_hint')}>
                                        <Input
                                            value={token}
                                            onChange={(e) => setToken(e.target.value)}
                                            placeholder="Paste API key"
                                        />
                                    </FormField>
                                )}
                                {type === 'basic' && (
                                    <div className="grid gap-5 lg:grid-cols-2">
                                        <FormField label={t('connections.username')}>
                                            <Input value={username} onChange={(e) => setUsername(e.target.value)} />
                                        </FormField>
                                        <FormField label={t('connections.password')}>
                                            <Input type="password" value={password} onChange={(e) => setPassword(e.target.value)} />
                                        </FormField>
                                    </div>
                                )}
                                {(type === 'header' || type === 'query') && (
                                    <div className="grid gap-5 lg:grid-cols-2">
                                        <FormField label={t('connections.header_name')}>
                                            <Input value={headerName} onChange={(e) => setHeaderName(e.target.value)} />
                                        </FormField>
                                        <FormField label={t('connections.value')}>
                                            <Input value={value} onChange={(e) => setValue(e.target.value)} />
                                        </FormField>
                                    </div>
                                )}
                                {type === 'oauth2' && (
                                    <div className="grid gap-5 lg:grid-cols-2">
                                        <FormField label={t('connections.grant')}>
                                            <NativeSelect
                                                value={grant}
                                                onValueChange={setGrant}
                                                options={[
                                                    { value: 'refresh_token', label: 'refresh_token' },
                                                    { value: 'client_credentials', label: 'client_credentials' },
                                                ]}
                                            />
                                        </FormField>
                                        <FormField label={t('connections.token_url')} required>
                                            <Input
                                                value={tokenUrl}
                                                onChange={(e) => setTokenUrl(e.target.value)}
                                                placeholder="https://auth.example.com/oauth/token"
                                            />
                                        </FormField>
                                        <FormField label={t('connections.scope')}>
                                            <Input value={scope} onChange={(e) => setScope(e.target.value)} />
                                        </FormField>
                                        <FormField label={t('connections.client_id')}>
                                            <Input value={clientId} onChange={(e) => setClientId(e.target.value)} />
                                        </FormField>
                                        <FormField label={t('connections.client_secret')}>
                                            <Input type="password" value={clientSecret} onChange={(e) => setClientSecret(e.target.value)} />
                                        </FormField>
                                        {grant === 'refresh_token' && (
                                            <FormField label={t('connections.refresh_token')}>
                                                <Input value={refreshToken} onChange={(e) => setRefreshToken(e.target.value)} />
                                            </FormField>
                                        )}
                                    </div>
                                )}
                            </div>
                        )}
                        {step === 2 && (
                            <div className="space-y-8">
                                <FormField label={t('connections.headers')} hint={t('connections.headers_hint')}>
                                    <KeyValueEditor
                                        rows={headers}
                                        onChange={setHeaders}
                                        keyPlaceholder={t('connections.key')}
                                        valuePlaceholder={t('connections.value_ph')}
                                        addLabel={t('connections.add_row')}
                                    />
                                </FormField>
                                <FormField label={t('connections.query')} hint={t('connections.query_hint')}>
                                    <KeyValueEditor
                                        rows={query}
                                        onChange={setQuery}
                                        keyPlaceholder={t('connections.key')}
                                        valuePlaceholder={t('connections.value_ph')}
                                        addLabel={t('connections.add_row')}
                                    />
                                </FormField>
                                {type === 'oauth2' && (
                                    <>
                                        <p className="text-[13px] font-medium text-slate-600">{t('connections.auth_extras')}</p>
                                        <p className="-mt-6 text-[13px] text-slate-500">{t('connections.auth_extras_desc')}</p>
                                        <FormField label={t('connections.auth_headers')}>
                                            <KeyValueEditor
                                                rows={authHeaders}
                                                onChange={setAuthHeaders}
                                                keyPlaceholder={t('connections.key')}
                                                valuePlaceholder={t('connections.value_ph')}
                                                addLabel={t('connections.add_row')}
                                            />
                                        </FormField>
                                        <FormField label={t('connections.auth_query')}>
                                            <KeyValueEditor
                                                rows={authQuery}
                                                onChange={setAuthQuery}
                                                keyPlaceholder={t('connections.key')}
                                                valuePlaceholder={t('connections.value_ph')}
                                                addLabel={t('connections.add_row')}
                                            />
                                        </FormField>
                                        <FormField label={t('connections.auth_body')}>
                                            <KeyValueEditor
                                                rows={authBody}
                                                onChange={setAuthBody}
                                                keyPlaceholder={t('connections.key')}
                                                valuePlaceholder={t('connections.value_ph')}
                                                addLabel={t('connections.add_row')}
                                            />
                                        </FormField>
                                    </>
                                )}
                            </div>
                        )}
                    </FormWizard>
                </div>
            )}

            <DataTable
                toolbar={
                    <ListFilters
                        search={q}
                        onSearch={setQ}
                        searchPlaceholder={t('filters.search')}
                        filters={[
                            {
                                id: 'type',
                                label: t('filters.type'),
                                value: typeFilter,
                                onChange: setTypeFilter,
                                options: facetOptionsFromRows(rows as unknown as Record<string, unknown>[], 'type', (value) => {
                                    const named = t(`filters.values.${value}`);
                                    return named.startsWith('filters.values.') ? value : named;
                                }),
                            },
                            {
                                id: 'status',
                                label: t('table.status'),
                                value: statusFilter,
                                onChange: setStatusFilter,
                                options: facetOptionsFromRows(rows as unknown as Record<string, unknown>[], 'status'),
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
                <Table>
                    <THead>
                        <Th>{t('table.name')}</Th>
                        <Th>{t('table.slug')}</Th>
                        <Th>{t('connections.type')}</Th>
                        <Th>{t('table.status')}</Th>
                        <Th>{t('table.actions')}</Th>
                    </THead>
                    <tbody>
                        {loading && <TableSkeleton cols={5} rows={4} />}
                        {!loading && visible.length === 0 && (
                            <tr>
                                <td colSpan={5}>
                                    <EmptyState
                                        icon={Link2}
                                        title={rows.length === 0 ? t('connections.empty') : t('filters.empty')}
                                        description={rows.length === 0 ? t('connections.hint') : t('filters.empty_hint')}
                                    />
                                </td>
                            </tr>
                        )}
                        {!loading &&
                            visible.map((row) => (
                                <Tr key={row.id}>
                                    <Td className="font-medium text-slate-900">{row.name}</Td>
                                    <Td>
                                        <code className="rounded-md bg-slate-100 px-1.5 py-0.5 font-mono text-[12px]">{row.slug}</code>
                                    </Td>
                                    <Td className="capitalize">{row.type}</Td>
                                    <Td>
                                        <StatusBadge status={row.status} label={row.status} />
                                    </Td>
                                    <Td>
                                        <div className="flex flex-wrap gap-1">
                                            <Button type="button" variant="ghost" onClick={() => openEdit(row)}>
                                                <Pencil className="h-4 w-4" />
                                                {t('actions.edit')}
                                            </Button>
                                            {row.type === 'oauth2' && (
                                                <Button type="button" variant="ghost" onClick={() => void refresh(row.id)}>
                                                    <RefreshCw className="h-4 w-4" />
                                                    {t('connections.refresh')}
                                                </Button>
                                            )}
                                            <Button type="button" variant="ghost" onClick={() => void remove(row.id)}>
                                                <Trash2 className="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </Td>
                                </Tr>
                            ))}
                    </tbody>
                </Table>
            </DataTable>
        </PageContainer>
    );
}
