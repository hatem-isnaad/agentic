import { Copy, Pencil } from 'lucide-react';
import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { adminApi } from '../lib/api';
import { useAdminConfig } from '../lib/config';
import { statusLabel } from '../lib/hooks';
import { useI18n } from '../lib/i18n';
import { Button } from '../components/ui/Button';
import { Card, CardBody } from '../components/ui/Card';
import { JsonHighlight } from '../components/ui/JsonHighlight';
import { PageContainer } from '../components/ui/PageContainer';
import { PageHeader } from '../components/ui/PageHeader';
import { Skeleton } from '../components/ui/Skeleton';
import { StatusBadge } from '../components/ui/StatusBadge';

type Props = {
    apiBase: string;
    resourceBase: string;
    titleKey?: string;
    nameField?: string;
    editSegment?: string;
};

export function EntityDetailPage({
    apiBase,
    resourceBase,
    titleKey,
    nameField = 'name',
    editSegment = 'edit',
}: Props) {
    const { slug, id, uuid } = useParams();
    const key = slug ?? id ?? uuid ?? '';
    const boot = useAdminConfig();
    const { t } = useI18n();
    const [data, setData] = useState<Record<string, unknown> | null>(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        adminApi
            .get<{ data: Record<string, unknown> }>(boot, `${apiBase}/${key}`)
            .then((res) => setData(res.data))
            .catch(() => setData(null))
            .finally(() => setLoading(false));
    }, [boot, apiBase, key]);

    const title = data ? String(data[nameField] ?? key) : key;
    const canEdit = Boolean(slug && !['workflow-runs', 'executions', 'conversations'].includes(resourceBase));

    const primaryFields = ['slug', 'status', 'description', 'driver', 'provider', 'model', 'agent_slug', 'workflow_slug', 'uuid'];
    const rows = data
        ? primaryFields
              .filter((f) => data[f] !== undefined && data[f] !== null && data[f] !== '')
              .map((f) => ({ key: f, value: data[f] }))
        : [];

    return (
        <PageContainer>
            {loading ? (
                <Skeleton className="mb-6 h-10 w-72" />
            ) : (
                <PageHeader
                    title={titleKey ? t(titleKey) : title}
                    description={data?.description ? String(data.description) : undefined}
                    actions={
                        canEdit ? (
                            <Link to={`/${resourceBase}/${slug}/${editSegment}`}>
                                <Button type="button" variant="secondary">
                                    <Pencil className="h-4 w-4" />
                                    {t('actions.edit')}
                                </Button>
                            </Link>
                        ) : undefined
                    }
                />
            )}

            <div className="grid gap-6 xl:grid-cols-12">
                <Card className="xl:col-span-4">
                    <CardBody>
                        <h2 className="mb-4 text-xs font-bold uppercase tracking-wider text-slate-500">
                            {t('detail.overview')}
                        </h2>
                        {loading ? (
                            <div className="space-y-3">
                                <Skeleton className="h-4 w-full" />
                                <Skeleton className="h-4 w-3/4" />
                            </div>
                        ) : (
                            <dl className="space-y-4">
                                {rows.map(({ key: k, value }) => (
                                    <div key={k}>
                                        <dt className="text-xs font-semibold uppercase text-slate-400">
                                            {t(`fields.${k}`) !== `fields.${k}` ? t(`fields.${k}`) : k}
                                        </dt>
                                        <dd className="mt-1 text-sm font-medium text-slate-800">
                                            {k === 'status' ? (
                                                <StatusBadge status={String(value)} label={statusLabel(t, value)} />
                                            ) : (
                                                String(value)
                                            )}
                                        </dd>
                                    </div>
                                ))}
                            </dl>
                        )}
                    </CardBody>
                </Card>
                <Card className="xl:col-span-8">
                    <CardBody>
                        <div className="mb-3 flex items-center justify-between">
                            <h2 className="text-xs font-bold uppercase tracking-wider text-slate-500">
                                {t('detail.payload')}
                            </h2>
                            <Button
                                type="button"
                                variant="ghost"
                                className="!py-1.5 !px-2"
                                onClick={() => navigator.clipboard.writeText(JSON.stringify(data, null, 2))}
                            >
                                <Copy className="h-4 w-4" />
                                {t('actions.copy')}
                            </Button>
                        </div>
                        {loading ? <Skeleton className="h-64 w-full" /> : <JsonHighlight data={data} />}
                    </CardBody>
                </Card>
            </div>
        </PageContainer>
    );
}
