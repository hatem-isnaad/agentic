import { Braces, BookOpen, Terminal } from 'lucide-react';
import { Link } from 'react-router-dom';
import { DOC_PART_LABEL, DOC_PART_ORDER, sortedCatalog } from '../../docs/catalog';
import { DocConfigureTable } from '../../components/docs/DocConfigureTable';
import { DeveloperDocsBanner } from '../../components/docs/DeveloperDocsBanner';
import { HostSetupChecklist } from '../../components/docs/HostSetupChecklist';
import { Button } from '../../components/ui/Button';
import { Card, CardBody } from '../../components/ui/Card';
import { PageHeader } from '../../components/ui/PageHeader';
import { useAdminConfig } from '../../lib/config';
import { useI18n } from '../../lib/i18n';

export function DocsIndexPage() {
    const { t } = useI18n();
    const boot = useAdminConfig();

    return (
        <div className="space-y-10">
            <PageHeader
                title={t('docs.page_title')}
                description={t('docs.index_intro')}
                actions={
                    <div className="flex flex-wrap gap-2">
                        <Link to="/docs/install">
                            <Button type="button">
                                <Terminal className="h-4 w-4" />
                                {t('docs.checklist_open_install')}
                            </Button>
                        </Link>
                        <Link to="/docs/widget-embed">
                            <Button type="button" variant="secondary">
                                {t('docs.catalog.widget_embed')}
                            </Button>
                        </Link>
                        <Link to="/docs/json-builder">
                            <Button type="button" variant="secondary">
                                <Braces className="h-4 w-4" />
                                {t('docs.open_json_builder')}
                            </Button>
                        </Link>
                        <Link to="/setup">
                            <Button type="button" variant="ghost" className="text-slate-600">
                                {t('docs.optional_ui_wizard')}
                            </Button>
                        </Link>
                    </div>
                }
            />

            <DeveloperDocsBanner />

            <HostSetupChecklist />

            <Card className="border-slate-200/80 bg-white">
                <CardBody className="space-y-4 text-sm leading-relaxed text-slate-700">
                    <p className="font-medium text-slate-900">{t('docs.index_promise')}</p>
                    <p className="text-slate-600">{t('docs.configure_intro')}</p>
                </CardBody>
            </Card>

            <section className="space-y-4">
                <div className="flex items-center gap-2">
                    <BookOpen className="h-4 w-4 text-slate-500" aria-hidden />
                    <h2 className="text-sm font-bold uppercase tracking-wide text-slate-500">
                        {t('docs.toc_configure')}
                    </h2>
                </div>
                <DocConfigureTable
                    adminApiPrefix={boot.apiPrefix}
                    widgetApiPrefix="/api/agentic/widget"
                />
                <p className="text-xs text-slate-500">{t('docs.vendor_md_configure')}</p>
            </section>

            {DOC_PART_ORDER.map((part) => {
                const items = sortedCatalog().filter((c) => c.part === part);
                return (
                    <section key={part}>
                        <h2 className="mb-3 text-sm font-bold uppercase tracking-wide text-slate-500">
                            {t(DOC_PART_LABEL[part])}
                        </h2>
                        <ol className="grid gap-2 sm:grid-cols-2">
                            {items.map((entry) => (
                                <li key={entry.slug}>
                                    <Link to={`/docs/${entry.slug}`} className="docs-chapter-index-link">
                                        <span className="docs-chapter-index-num">{entry.order}</span>
                                        <span className="font-medium text-slate-900">
                                            {t(`docs.catalog.${entry.labelKey}`)}
                                        </span>
                                    </Link>
                                </li>
                            ))}
                        </ol>
                    </section>
                );
            })}

            <p className="text-xs text-slate-500">{t('docs.readme_hint')}</p>
        </div>
    );
}
