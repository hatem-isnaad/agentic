import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { adminApi } from '../lib/api';
import { useAdminConfig } from '../lib/config';
import { useI18n } from '../lib/i18n';
import { Button } from '../components/ui/Button';
import { Card, CardBody } from '../components/ui/Card';
import { FormField } from '../components/ui/FormField';
import { Input, Textarea } from '../components/ui/Input';
import { NativeSelect } from '../components/ui/NativeSelect';
import { JsonHighlight } from '../components/ui/JsonHighlight';
import { PageContainer } from '../components/ui/PageContainer';
import { PageHeader } from '../components/ui/PageHeader';
import { Skeleton } from '../components/ui/Skeleton';
import { StatusBadge } from '../components/ui/StatusBadge';
import { HelpCallout } from '../components/ui/HelpCallout';
import { FileUp, Pencil } from 'lucide-react';

const FORMATS = ['text', 'markdown', 'html', 'json', 'pdf'].map((v) => ({ value: v, label: v }));

export function KnowledgeSourceDetailPage() {
    const { slug = '' } = useParams();
    const boot = useAdminConfig();
    const { t } = useI18n();
    const [data, setData] = useState<Record<string, unknown> | null>(null);
    const [loading, setLoading] = useState(true);
    const [format, setFormat] = useState('markdown');
    const [rawText, setRawText] = useState('');
    const [urls, setUrls] = useState('');
    const [searchQuery, setSearchQuery] = useState('');
    const [searchResults, setSearchResults] = useState<Record<string, unknown>[]>([]);
    const [message, setMessage] = useState<string | null>(null);
    const [error, setError] = useState<string | null>(null);
    const [busy, setBusy] = useState(false);
    const [pdfFiles, setPdfFiles] = useState<File[]>([]);

    useEffect(() => {
        adminApi
            .get<{ data: Record<string, unknown> }>(boot, `/knowledge-sources/${slug}`)
            .then((res) => setData(res.data))
            .catch(() => setData(null))
            .finally(() => setLoading(false));
    }, [boot, slug]);

    const ingest = async () => {
        setBusy(true);
        setError(null);
        setMessage(null);
        try {
            const urlList = urls
                .split('\n')
                .map((u) => u.trim())
                .filter(Boolean);

            let res: { meta?: { documents?: number; chunks?: number } };

            if (pdfFiles.length > 0) {
                const form = new FormData();
                form.append('format', 'pdf');
                form.append('reindex', '1');
                pdfFiles.forEach((file) => form.append('files[]', file));
                res = await adminApi.postForm<{ meta?: { documents?: number; chunks?: number } }>(
                    boot,
                    `/knowledge-sources/${slug}/ingest`,
                    form,
                );
                setPdfFiles([]);
            } else {
                res = await adminApi.post<{ meta?: { documents?: number; chunks?: number } }>(
                    boot,
                    `/knowledge-sources/${slug}/ingest`,
                    {
                        format,
                        raw_text: rawText,
                        urls: urlList.length ? urlList : undefined,
                        reindex: true,
                    },
                );
            }

            const meta = res.meta;
            setMessage(
                t('knowledge.ingest_success', {
                    documents: String(meta?.documents ?? '?'),
                    chunks: String(meta?.chunks ?? '?'),
                }),
            );
            const refreshed = await adminApi.get<{ data: Record<string, unknown> }>(boot, `/knowledge-sources/${slug}`);
            setData(refreshed.data);
        } catch (e) {
            setError(e instanceof Error ? e.message : 'Ingest failed');
        } finally {
            setBusy(false);
        }
    };

    const onPdfChange = (event: React.ChangeEvent<HTMLInputElement>) => {
        const list = event.target.files ? Array.from(event.target.files) : [];
        setPdfFiles(list);
        if (list.length > 0) {
            setFormat('pdf');
        }
    };

    const reindex = async () => {
        setBusy(true);
        setError(null);
        try {
            await adminApi.post(boot, `/knowledge-sources/${slug}/index`, {});
            setMessage(t('knowledge.reindex_success'));
        } catch (e) {
            setError(e instanceof Error ? e.message : 'Reindex failed');
        } finally {
            setBusy(false);
        }
    };

    const search = async () => {
        setBusy(true);
        setError(null);
        try {
            const res = await adminApi.post<{ data: Record<string, unknown>[] }>(
                boot,
                `/knowledge-sources/${slug}/search`,
                { query: searchQuery, limit: 5 },
            );
            setSearchResults(res.data);
        } catch (e) {
            setError(e instanceof Error ? e.message : 'Search failed');
        } finally {
            setBusy(false);
        }
    };

    if (loading) {
        return (
            <PageContainer>
                <Skeleton className="h-10 w-72" />
            </PageContainer>
        );
    }

    const title = String(data?.name ?? slug);

    return (
        <PageContainer>
            <PageHeader
                title={title}
                description={data?.description ? String(data.description) : undefined}
                actions={
                    <Link to={`/knowledge-sources/${slug}/edit`}>
                        <Button type="button" variant="secondary">
                            <Pencil className="h-4 w-4" />
                            {t('actions.edit')}
                        </Button>
                    </Link>
                }
            />

            <HelpCallout>{t('knowledge.detail_hint')}</HelpCallout>

            <div className="mb-6 flex flex-wrap gap-3 text-sm">
                <StatusBadge status={String(data?.status ?? 'draft')} />
                <span className="text-slate-600">
                    {t('fields.driver')}: <strong>{String(data?.driver ?? '')}</strong>
                </span>
            </div>

            {message && (
                <div className="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{message}</div>
            )}
            {error && (
                <div className="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{error}</div>
            )}

            <div className="grid gap-6 xl:grid-cols-2">
                <Card>
                    <CardBody className="space-y-4">
                        <h2 className="text-sm font-bold uppercase tracking-wide text-slate-500">{t('knowledge.ingest_title')}</h2>
                        <p className="text-sm text-slate-600">{t('knowledge.ingest_intro')}</p>

                        <div className="rounded-xl border border-dashed border-slate-300 bg-slate-50/80 p-4">
                            <div className="flex items-start gap-3">
                                <FileUp className="mt-0.5 h-5 w-5 shrink-0 text-slate-500" />
                                <div className="min-w-0 flex-1 space-y-2">
                                    <p className="text-sm font-medium text-slate-800">{t('knowledge.pdf_upload_title')}</p>
                                    <p className="text-xs text-slate-600">{t('knowledge.pdf_upload_hint')}</p>
                                    <input
                                        type="file"
                                        accept="application/pdf,.pdf"
                                        multiple
                                        className="block w-full text-sm text-slate-700 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-900 file:px-3 file:py-2 file:text-sm file:font-medium file:text-white"
                                        onChange={onPdfChange}
                                    />
                                    {pdfFiles.length > 0 && (
                                        <p className="text-xs text-emerald-800">
                                            {t('knowledge.pdf_selected', { count: String(pdfFiles.length) })}
                                        </p>
                                    )}
                                </div>
                            </div>
                        </div>

                        <p className="text-xs font-medium uppercase tracking-wide text-slate-400">{t('knowledge.or_paste')}</p>

                        <FormField label={t('knowledge.fields.format')}>
                            <NativeSelect value={format} onValueChange={setFormat} options={FORMATS} />
                        </FormField>
                        <FormField label={t('knowledge.fields.raw_text')} hint={t('knowledge.raw_text_hint')}>
                            <Textarea value={rawText} onChange={(e) => setRawText(e.target.value)} rows={8} />
                        </FormField>
                        <FormField label={t('knowledge.fields.urls')} hint={t('knowledge.urls_hint')}>
                            <Textarea value={urls} onChange={(e) => setUrls(e.target.value)} rows={3} />
                        </FormField>
                        <div className="flex flex-wrap gap-2">
                            <Button
                                type="button"
                                disabled={busy || (pdfFiles.length === 0 && !rawText.trim() && !urls.trim())}
                                onClick={ingest}
                            >
                                {t('knowledge.ingest_action')}
                            </Button>
                            <Button type="button" variant="secondary" disabled={busy} onClick={reindex}>
                                {t('knowledge.reindex_action')}
                            </Button>
                        </div>
                    </CardBody>
                </Card>

                <Card>
                    <CardBody className="space-y-4">
                        <h2 className="text-sm font-bold uppercase tracking-wide text-slate-500">{t('knowledge.search_title')}</h2>
                        <FormField label={t('knowledge.fields.query')}>
                            <Input value={searchQuery} onChange={(e) => setSearchQuery(e.target.value)} />
                        </FormField>
                        <Button type="button" variant="secondary" disabled={busy || !searchQuery.trim()} onClick={search}>
                            {t('knowledge.search_action')}
                        </Button>
                        {searchResults.length > 0 && (
                            <ul className="space-y-3 text-sm">
                                {searchResults.map((row, i) => (
                                    <li key={i} className="rounded-lg border border-slate-200 bg-slate-50 p-3">
                                        <p className="text-slate-800">{String(row.content ?? '')}</p>
                                        <p className="mt-1 text-xs text-slate-500">
                                            score: {String(row.score ?? '')}
                                        </p>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </CardBody>
                </Card>
            </div>

            <Card className="mt-6">
                <CardBody>
                    <h2 className="mb-3 text-sm font-bold uppercase tracking-wide text-slate-500">{t('detail.payload')}</h2>
                    <JsonHighlight value={data ?? {}} />
                </CardBody>
            </Card>
        </PageContainer>
    );
}
