import { Phone, Plus, Trash2 } from 'lucide-react';
import { useCallback, useEffect, useMemo, useState } from 'react';
import { adminApi, type Paginated } from '../lib/api';
import { useAdminConfig } from '../lib/config';
import { useI18n } from '../lib/i18n';
import { Button } from '../components/ui/Button';
import { Card, CardBody } from '../components/ui/Card';
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
import { ALL_FILTER, facetOptionsFromRows, matchesFacet, matchesSearch } from '../lib/listFilters';

type Row = {
    id: number;
    name: string;
    slug: string;
    channel: string;
    driver: string;
    agent_slug: string | null;
    external_id: string | null;
    display_number: string | null;
    status: string;
    has_credentials: boolean;
};

type Option = { channel: string; drivers: string[] };

export function ChannelAccountsPage() {
    const boot = useAdminConfig();
    const { t } = useI18n();
    const [rows, setRows] = useState<Row[]>([]);
    const [options, setOptions] = useState<Option[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [show, setShow] = useState(false);
    const [channel, setChannel] = useState('whatsapp');
    const [driver, setDriver] = useState('meta_cloud');
    const [name, setName] = useState('');
    const [slug, setSlug] = useState('');
    const [agentSlug, setAgentSlug] = useState('');
    const [externalId, setExternalId] = useState('');
    const [displayNumber, setDisplayNumber] = useState('');
    const [accessToken, setAccessToken] = useState('');
    const [appSecret, setAppSecret] = useState('');
    const [verifyToken, setVerifyToken] = useState('');
    const [sidecarUrl, setSidecarUrl] = useState('');
    const [sidecarSecret, setSidecarSecret] = useState('');
    const [q, setQ] = useState('');
    const [channelFilter, setChannelFilter] = useState(ALL_FILTER);
    const [driverFilter, setDriverFilter] = useState(ALL_FILTER);
    const [agentFilter, setAgentFilter] = useState(ALL_FILTER);

    const drivers = useMemo(
        () => options.find((row) => row.channel === channel)?.drivers ?? ['embed'],
        [options, channel],
    );

    const load = useCallback(async () => {
        setLoading(true);
        try {
            const [list, opts] = await Promise.all([
                adminApi.get<Paginated<Row>>(boot, '/channel-accounts?per_page=100'),
                adminApi.get<{ data: Option[] }>(boot, '/channel-accounts/options'),
            ]);
            setRows(list.data);
            setOptions(opts.data);
        } catch {
            setRows([]);
        } finally {
            setLoading(false);
        }
    }, [boot]);

    useEffect(() => {
        void load();
    }, [load]);

    useEffect(() => {
        if (!drivers.includes(driver)) {
            setDriver(drivers[0] ?? 'embed');
        }
    }, [drivers, driver]);

    const resetForm = () => {
        setName('');
        setSlug('');
        setAgentSlug('');
        setExternalId('');
        setDisplayNumber('');
        setAccessToken('');
        setAppSecret('');
        setVerifyToken('');
        setSidecarUrl('');
        setSidecarSecret('');
    };

    const create = async () => {
        setError(null);
        const credentials: Record<string, string> = {};
        const config: Record<string, string> = {};
        if (driver === 'meta_cloud') {
            if (accessToken) credentials.access_token = accessToken;
            if (appSecret) credentials.app_secret = appSecret;
            if (verifyToken) credentials.verify_token = verifyToken;
        }
        if (driver === 'webjs') {
            if (sidecarUrl) config.sidecar_url = sidecarUrl;
            if (sidecarSecret) credentials.sidecar_secret = sidecarSecret;
        }
        try {
            await adminApi.post(boot, '/channel-accounts', {
                name: name.trim() || slug.trim(),
                slug: slug.trim(),
                channel,
                driver,
                agent_slug: agentSlug.trim() || null,
                external_id: externalId.trim() || null,
                display_number: displayNumber.trim() || null,
                status: 'active',
                config: Object.keys(config).length ? config : undefined,
                credentials: Object.keys(credentials).length ? credentials : undefined,
            });
            setShow(false);
            resetForm();
            await load();
        } catch (e) {
            setError(e instanceof Error ? e.message : 'Save failed');
        }
    };

    const remove = async (id: number) => {
        if (!window.confirm(t('actions.confirm_delete'))) return;
        await adminApi.delete(boot, `/channel-accounts/${id}`);
        await load();
    };

    const visible = useMemo(
        () =>
            rows.filter(
                (row) =>
                    matchesSearch(row, q) &&
                    matchesFacet(row as unknown as Record<string, unknown>, 'channel', channelFilter) &&
                    matchesFacet(row as unknown as Record<string, unknown>, 'driver', driverFilter) &&
                    matchesFacet(row as unknown as Record<string, unknown>, 'agent_slug', agentFilter),
            ),
        [agentFilter, channelFilter, driverFilter, q, rows],
    );

    return (
        <PageContainer>
            <PageHeader
                title={t('channels.title')}
                description={t('channels.subtitle')}
                actions={
                    <Button type="button" onClick={() => setShow((v) => !v)}>
                        <Plus className="h-4 w-4" />
                        {t('channels.create')}
                    </Button>
                }
            />
            <HelpCallout>{t('channels.hint')}</HelpCallout>
            {error && <ErrorBanner message={error} />}

            {show && (
                <Card className="mb-6">
                    <CardBody className="space-y-4">
                        <FormField label={t('fields.name')} required>
                            <Input value={name} onChange={(e) => setName(e.target.value)} />
                        </FormField>
                        <FormField label={t('fields.slug')} required>
                            <Input value={slug} onChange={(e) => setSlug(e.target.value)} />
                        </FormField>
                        <FormField label={t('channels.channel')}>
                            <NativeSelect
                                value={channel}
                                onValueChange={setChannel}
                                options={(options.length ? options : [{ channel: 'whatsapp', drivers: ['meta_cloud'] }]).map((row) => ({
                                    value: row.channel,
                                    label: row.channel,
                                }))}
                            />
                        </FormField>
                        <FormField label={t('channels.driver')}>
                            <NativeSelect
                                value={driver}
                                onValueChange={setDriver}
                                options={drivers.map((value) => ({ value, label: value }))}
                            />
                        </FormField>
                        <FormField label={t('channels.agent')} hint={t('channels.agent_hint')}>
                            <Input value={agentSlug} onChange={(e) => setAgentSlug(e.target.value)} placeholder="support" />
                        </FormField>
                        <FormField label={t('channels.external_id')} hint={t('channels.external_id_hint')}>
                            <Input value={externalId} onChange={(e) => setExternalId(e.target.value)} />
                        </FormField>
                        <FormField label={t('channels.display_number')}>
                            <Input value={displayNumber} onChange={(e) => setDisplayNumber(e.target.value)} placeholder="+20100000000" />
                        </FormField>
                        {driver === 'meta_cloud' && (
                            <>
                                <FormField label={t('channels.access_token')} hint={t('channels.secret_hint')}>
                                    <Input value={accessToken} onChange={(e) => setAccessToken(e.target.value)} placeholder="env:META_WHATSAPP_TOKEN" />
                                </FormField>
                                <FormField label={t('channels.app_secret')}>
                                    <Input type="password" value={appSecret} onChange={(e) => setAppSecret(e.target.value)} />
                                </FormField>
                                <FormField label={t('channels.verify_token')}>
                                    <Input value={verifyToken} onChange={(e) => setVerifyToken(e.target.value)} />
                                </FormField>
                            </>
                        )}
                        {driver === 'webjs' && (
                            <>
                                <FormField label={t('channels.sidecar_url')}>
                                    <Input value={sidecarUrl} onChange={(e) => setSidecarUrl(e.target.value)} placeholder="https://sidecar.example.com" />
                                </FormField>
                                <FormField label={t('channels.sidecar_secret')}>
                                    <Input type="password" value={sidecarSecret} onChange={(e) => setSidecarSecret(e.target.value)} />
                                </FormField>
                            </>
                        )}
                        <Button type="button" onClick={() => void create()}>
                            {t('actions.save')}
                        </Button>
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
                                id: 'channel',
                                label: t('filters.channel'),
                                value: channelFilter,
                                onChange: setChannelFilter,
                                options: facetOptionsFromRows(rows as unknown as Record<string, unknown>[], 'channel'),
                            },
                            {
                                id: 'driver',
                                label: t('table.driver'),
                                value: driverFilter,
                                onChange: setDriverFilter,
                                options: facetOptionsFromRows(rows as unknown as Record<string, unknown>[], 'driver'),
                            },
                            {
                                id: 'agent',
                                label: t('table.agent'),
                                value: agentFilter,
                                onChange: setAgentFilter,
                                options: facetOptionsFromRows(rows as unknown as Record<string, unknown>[], 'agent_slug'),
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
                        <Th>{t('channels.channel')}</Th>
                        <Th>{t('channels.driver')}</Th>
                        <Th>{t('channels.agent')}</Th>
                        <Th>{t('channels.external_id')}</Th>
                        <Th>{t('table.actions')}</Th>
                    </THead>
                    <tbody>
                        {loading && <TableSkeleton cols={6} rows={4} />}
                        {!loading && visible.length === 0 && (
                            <tr>
                                <td colSpan={6}>
                                    <EmptyState
                                        icon={Phone}
                                        title={rows.length === 0 ? t('channels.empty') : t('filters.empty')}
                                        description={rows.length === 0 ? t('channels.hint') : t('filters.empty_hint')}
                                    />
                                </td>
                            </tr>
                        )}
                        {!loading &&
                            visible.map((row) => (
                                <Tr key={row.id}>
                                    <Td className="font-medium text-slate-900">{row.name}</Td>
                                    <Td>{row.channel}</Td>
                                    <Td>
                                        <code className="rounded-md bg-slate-100 px-1.5 py-0.5 font-mono text-[12px]">{row.driver}</code>
                                    </Td>
                                    <Td>{row.agent_slug || '—'}</Td>
                                    <Td>{row.external_id || '—'}</Td>
                                    <Td>
                                        <Button type="button" variant="ghost" onClick={() => void remove(row.id)}>
                                            <Trash2 className="h-4 w-4" />
                                        </Button>
                                    </Td>
                                </Tr>
                            ))}
                    </tbody>
                </Table>
            </DataTable>
        </PageContainer>
    );
}
