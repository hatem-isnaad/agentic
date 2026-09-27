import { useEffect, useState } from 'react';
import { adminApi } from '../lib/api';
import { useAdminConfig } from '../lib/config';
import { useI18n } from '../lib/i18n';
import { Card, CardBody } from '../components/ui/Card';
import { JsonHighlight } from '../components/ui/JsonHighlight';
import { PageContainer } from '../components/ui/PageContainer';
import { PageHeader } from '../components/ui/PageHeader';
import { Skeleton } from '../components/ui/Skeleton';

export function SettingsPage() {
    const boot = useAdminConfig();
    const { t } = useI18n();
    const [data, setData] = useState<Record<string, unknown> | null>(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        adminApi
            .get<{ data: Record<string, unknown> }>(boot, '/settings')
            .then((res) => setData(res.data))
            .catch(() => setData(null))
            .finally(() => setLoading(false));
    }, [boot]);

    const features = (data?.features ?? {}) as Record<string, boolean>;

    return (
        <PageContainer>
            <PageHeader title={t('settings.title')} description={t('settings.intro')} />
            {loading ? (
                <Skeleton className="h-64 w-full" />
            ) : (
                <div className="space-y-6">
                    <Card>
                        <CardBody>
                            <h2 className="mb-4 text-sm font-bold uppercase tracking-wide text-slate-500">
                                {t('settings.features_title')}
                            </h2>
                            <ul className="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                {Object.entries(features).map(([key, on]) => (
                                    <li key={key} className="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-2 text-sm">
                                        <span className="font-mono text-slate-700">{key}</span>
                                        <span className={on ? 'text-emerald-600 font-semibold' : 'text-slate-400'}>
                                            {on ? t('settings.on') : t('settings.off')}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                            <p className="mt-4 text-sm text-slate-600">{t('settings.env_hint')}</p>
                        </CardBody>
                    </Card>
                    <Card>
                        <CardBody>
                            <h2 className="mb-3 text-sm font-bold uppercase tracking-wide text-slate-500">
                                {t('settings.config_title')}
                            </h2>
                            <JsonHighlight value={data ?? {}} />
                        </CardBody>
                    </Card>
                </div>
            )}
        </PageContainer>
    );
}
