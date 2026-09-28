import { useEffect, useState } from 'react';
import { Coins } from 'lucide-react';
import { adminApi } from '../lib/api';
import { useAdminConfig } from '../lib/config';
import { useI18n } from '../lib/i18n';
import { Card, CardBody } from '../components/ui/Card';
import { PageContainer } from '../components/ui/PageContainer';
import { PageHeader } from '../components/ui/PageHeader';
import { Skeleton } from '../components/ui/Skeleton';
import { Table, THead, Th, Td, Tr } from '../components/ui/DataTable';

type UsageRow = {
    agent: string;
    executions: number;
    tokens_in: number;
    tokens_out: number;
    estimated_usd: number;
};

type RecentRow = {
    id: string;
    agent: string;
    model: string;
    tokens_in: number;
    tokens_out: number;
    estimated_usd: number;
    created_at?: string | null;
};

type DayRow = {
    date: string;
    executions: number;
    tokens_in: number;
    tokens_out: number;
    estimated_usd: number;
};

type UsageData = {
    executions: number;
    tokens_in: number;
    tokens_out: number;
    tokens_total: number;
    estimated_usd: number;
    currency: string;
    by_agent: UsageRow[];
    by_day: DayRow[];
    avg_usd_per_message: number;
    messages_per_20_usd: number;
    recent: RecentRow[];
};

function money(value: number, currency: string): string {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency, maximumFractionDigits: 4 }).format(value);
}

export function UsagePage() {
    const boot = useAdminConfig();
    const { t } = useI18n();
    const [data, setData] = useState<UsageData | null>(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        adminApi
            .get<{ data: UsageData }>(boot, '/usage')
            .then((res) => setData(res.data))
            .catch(() => setData(null))
            .finally(() => setLoading(false));
    }, [boot]);

    return (
        <PageContainer>
            <PageHeader title={t('usage.title')} description={t('usage.intro')} />

            <div className="mb-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                {[
                    [t('usage.executions'), data?.executions],
                    [t('usage.tokens_in'), data?.tokens_in],
                    [t('usage.tokens_out'), data?.tokens_out],
                    [t('usage.estimated'), data ? money(data.estimated_usd, data.currency) : null],
                    [t('usage.avg_message'), data ? money(data.avg_usd_per_message, data.currency) : null],
                    [t('usage.per_20'), data?.messages_per_20_usd],
                ].map(([label, value]) => (
                    <Card key={String(label)}>
                        <CardBody className="py-4">
                            <p className="text-[11px] font-medium text-slate-500">{label}</p>
                            {loading ? (
                                <Skeleton className="mt-1 h-7 w-16" />
                            ) : (
                                <p className="mt-1 text-2xl font-semibold tabular-nums text-slate-950">{value ?? 0}</p>
                            )}
                        </CardBody>
                    </Card>
                ))}
            </div>

            <Card className="mb-6">
                <CardBody>
                    <h2 className="mb-3 flex items-center gap-2 text-sm font-semibold text-slate-900">
                        <Coins className="h-4 w-4" />
                        {t('usage.by_agent')}
                    </h2>
                    {loading ? (
                        <Skeleton className="h-24 w-full" />
                    ) : (data?.by_agent ?? []).length === 0 ? (
                        <p className="text-sm text-slate-500">{t('usage.empty')}</p>
                    ) : (
                        <Table>
                            <THead>
                                <Th>{t('table.agent')}</Th>
                                <Th>{t('usage.executions')}</Th>
                                <Th>{t('usage.tokens_in')}</Th>
                                <Th>{t('usage.tokens_out')}</Th>
                                <Th>{t('usage.estimated')}</Th>
                            </THead>
                            <tbody>
                                {data?.by_agent.map((row) => (
                                    <Tr key={row.agent}>
                                        <Td>{row.agent}</Td>
                                        <Td>{row.executions}</Td>
                                        <Td>{row.tokens_in}</Td>
                                        <Td>{row.tokens_out}</Td>
                                        <Td>{money(row.estimated_usd, data.currency)}</Td>
                                    </Tr>
                                ))}
                            </tbody>
                        </Table>
                    )}
                </CardBody>
            </Card>

            <Card className="mb-6">
                <CardBody>
                    <h2 className="mb-3 text-sm font-semibold text-slate-900">{t('usage.by_day')}</h2>
                    {loading ? (
                        <Skeleton className="h-24 w-full" />
                    ) : (data?.by_day ?? []).length === 0 ? (
                        <p className="text-sm text-slate-500">{t('usage.empty')}</p>
                    ) : (
                        <Table>
                            <THead>
                                <Th>{t('usage.date')}</Th>
                                <Th>{t('usage.executions')}</Th>
                                <Th>{t('usage.tokens_in')}</Th>
                                <Th>{t('usage.tokens_out')}</Th>
                                <Th>{t('usage.estimated')}</Th>
                            </THead>
                            <tbody>
                                {data?.by_day.map((row) => (
                                    <Tr key={row.date}>
                                        <Td>{row.date}</Td>
                                        <Td>{row.executions}</Td>
                                        <Td>{row.tokens_in}</Td>
                                        <Td>{row.tokens_out}</Td>
                                        <Td>{money(row.estimated_usd, data.currency)}</Td>
                                    </Tr>
                                ))}
                            </tbody>
                        </Table>
                    )}
                </CardBody>
            </Card>

            <Card>
                <CardBody>
                    <h2 className="mb-3 text-sm font-semibold text-slate-900">{t('usage.recent')}</h2>
                    {loading ? (
                        <Skeleton className="h-24 w-full" />
                    ) : (data?.recent ?? []).length === 0 ? (
                        <p className="text-sm text-slate-500">{t('usage.empty')}</p>
                    ) : (
                        <Table>
                            <THead>
                                <Th>{t('table.agent')}</Th>
                                <Th>{t('usage.model')}</Th>
                                <Th>{t('usage.tokens_in')}</Th>
                                <Th>{t('usage.tokens_out')}</Th>
                                <Th>{t('usage.estimated')}</Th>
                            </THead>
                            <tbody>
                                {data?.recent.map((row) => (
                                    <Tr key={row.id}>
                                        <Td>{row.agent}</Td>
                                        <Td>{row.model || '—'}</Td>
                                        <Td>{row.tokens_in}</Td>
                                        <Td>{row.tokens_out}</Td>
                                        <Td>{money(row.estimated_usd, data.currency)}</Td>
                                    </Tr>
                                ))}
                            </tbody>
                        </Table>
                    )}
                </CardBody>
            </Card>
        </PageContainer>
    );
}
