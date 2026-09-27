import { Pencil } from 'lucide-react';
import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { adminApi } from '../lib/api';
import { useAdminConfig } from '../lib/config';
import { useI18n } from '../lib/i18n';
import { Button } from '../components/ui/Button';
import { Card, CardBody } from '../components/ui/Card';
import { FormField } from '../components/ui/FormField';
import { JsonField, parseJsonObject } from '../components/ui/JsonField';
import { JsonHighlight } from '../components/ui/JsonHighlight';
import { PageContainer } from '../components/ui/PageContainer';
import { PageHeader } from '../components/ui/PageHeader';
import { Skeleton } from '../components/ui/Skeleton';

export function WorkflowDetailPage() {
    const { slug = '' } = useParams();
    const boot = useAdminConfig();
    const { t } = useI18n();
    const [data, setData] = useState<Record<string, unknown> | null>(null);
    const [loading, setLoading] = useState(true);
    const [inputJson, setInputJson] = useState('{}');
    const [result, setResult] = useState<unknown>(null);
    const [error, setError] = useState<string | null>(null);
    const [busy, setBusy] = useState(false);

    useEffect(() => {
        adminApi
            .get<{ data: Record<string, unknown> }>(boot, `/workflows/${slug}`)
            .then((res) => setData(res.data))
            .catch(() => setData(null))
            .finally(() => setLoading(false));
    }, [boot, slug]);

    const execute = async () => {
        setBusy(true);
        setError(null);
        try {
            const input = parseJsonObject(inputJson, 'Input');
            const res = await adminApi.post<{ data: unknown }>(boot, `/workflows/${slug}/execute`, { input });
            setResult(res.data);
        } catch (e) {
            setError(e instanceof Error ? e.message : 'Execute failed');
        } finally {
            setBusy(false);
        }
    };

    return (
        <PageContainer>
            {loading ? (
                <Skeleton className="h-10 w-72" />
            ) : (
                <PageHeader
                    title={String(data?.name ?? slug)}
                    actions={
                        <Link to={`/workflows/${slug}/edit`}>
                            <Button type="button" variant="secondary">
                                <Pencil className="h-4 w-4" />
                                {t('actions.edit')}
                            </Button>
                        </Link>
                    }
                />
            )}

            <div className="grid gap-6 xl:grid-cols-2">
                <Card>
                    <CardBody className="space-y-4">
                        <h2 className="text-sm font-bold uppercase tracking-wide text-slate-500">{t('workflows.execute_title')}</h2>
                        <FormField label={t('workflows.fields.input')} hint={t('workflows.input_hint')}>
                            <JsonField value={inputJson} onChange={setInputJson} rows={8} />
                        </FormField>
                        <Button type="button" disabled={busy} onClick={execute}>{t('workflows.execute_action')}</Button>
                        {error && <p className="text-sm text-red-600">{error}</p>}
                        {result !== null && <JsonHighlight value={result} />}
                    </CardBody>
                </Card>
                <Card>
                    <CardBody>
                        <h2 className="mb-3 text-sm font-bold uppercase tracking-wide text-slate-500">{t('detail.payload')}</h2>
                        <JsonHighlight value={data ?? {}} />
                    </CardBody>
                </Card>
            </div>
        </PageContainer>
    );
}
