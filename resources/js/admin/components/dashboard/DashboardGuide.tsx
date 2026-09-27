import { ArrowRight, BookOpen, ChevronDown, Circle, Terminal } from 'lucide-react';
import { useState } from 'react';
import { Link } from 'react-router-dom';
import { useAdminConfig } from '../../lib/config';
import { useI18n } from '../../lib/i18n';
import { Card, CardBody } from '../ui/Card';

const steps = [
    { n: 1, link: null as string | null, linkKey: null as string | null },
    { n: 2, link: '/agents', linkKey: 'step2_link' },
    { n: 3, link: '/skills', linkKey: 'step3_link' },
    { n: 4, link: '/knowledge-sources', linkKey: 'step4_link' },
    { n: 5, link: null, linkKey: 'step5_link', externalWidget: true },
    { n: 6, link: '/workflows', linkKey: 'step6_link' },
];

export function DashboardGuide() {
    const { t } = useI18n();
    const boot = useAdminConfig();
    const widgetPath = boot.webPrefix.replace(/\/admin\/?$/, '/widget') + '?agent=support';
    const [open, setOpen] = useState(false);

    return (
        <Card className="mb-8 overflow-hidden border-slate-200/80 bg-white">
            <button
                type="button"
                className="flex w-full items-center justify-between gap-3 px-5 py-4 text-left hover:bg-slate-50/80"
                onClick={() => setOpen((v) => !v)}
            >
                <div>
                    <h2 className="text-sm font-bold text-slate-900">{t('guide.title')}</h2>
                    <p className="mt-0.5 text-xs text-slate-500">{t('guide.collapsed_hint')}</p>
                </div>
                <ChevronDown className={`h-5 w-5 shrink-0 text-slate-400 transition ${open ? 'rotate-180' : ''}`} />
            </button>
            {open && (
            <CardBody className="space-y-8 border-t border-slate-100 pt-6">
                <p className="text-sm leading-relaxed text-slate-600">{t('guide.subtitle')}</p>

                <div className="rounded-xl border border-slate-200/80 bg-white/80 p-5">
                    <h3 className="text-sm font-bold uppercase tracking-wide text-slate-500">{t('guide.how_title')}</h3>
                    <ul className="mt-4 space-y-3 text-sm text-slate-700">
                        <li className="flex gap-3">
                            <Circle className="mt-1.5 h-2 w-2 shrink-0 fill-brand-500 text-brand-500" />
                            {t('guide.how_1')}
                        </li>
                        <li className="flex gap-3">
                            <Circle className="mt-1.5 h-2 w-2 shrink-0 fill-brand-500 text-brand-500" />
                            {t('guide.how_2')}
                        </li>
                        <li className="flex gap-3">
                            <Circle className="mt-1.5 h-2 w-2 shrink-0 fill-brand-500 text-brand-500" />
                            {t('guide.how_3')}
                        </li>
                    </ul>
                    <p className="mt-4 rounded-lg bg-slate-900 px-4 py-3 text-center text-xs font-semibold tracking-wide text-slate-200">
                        {t('guide.flow')}
                    </p>
                </div>

                <div>
                    <h3 className="text-sm font-bold uppercase tracking-wide text-slate-500">{t('guide.steps_title')}</h3>
                    <ol className="mt-4 grid gap-4 lg:grid-cols-2">
                        {steps.map(({ n, link, linkKey, externalWidget }) => (
                            <li
                                key={n}
                                className="flex gap-4 rounded-xl border border-slate-200/70 bg-white p-4 shadow-sm"
                            >
                                <span
                                    className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-brand-600 text-sm font-bold text-white"
                                >
                                    {n}
                                </span>
                                <div className="min-w-0 flex-1">
                                    <p className="font-semibold text-slate-900">{t(`guide.step${n}_title`)}</p>
                                    <p className="mt-1 text-sm leading-relaxed text-slate-600">{t(`guide.step${n}_body`)}</p>
                                    {linkKey && link && (
                                        <Link
                                            to={link}
                                            className="mt-2 inline-flex items-center gap-1 text-sm font-semibold text-brand-600 hover:text-brand-700"
                                        >
                                            {t(linkKey)}
                                            <ArrowRight className="h-3.5 w-3.5" />
                                        </Link>
                                    )}
                                    {linkKey && externalWidget && (
                                        <a
                                            href={widgetPath}
                                            className="mt-2 inline-flex items-center gap-1 text-sm font-semibold text-brand-600 hover:text-brand-700"
                                        >
                                            {t(linkKey)}
                                            <ArrowRight className="h-3.5 w-3.5" />
                                        </a>
                                    )}
                                </div>
                            </li>
                        ))}
                    </ol>
                </div>

                <div className="grid gap-4 md:grid-cols-2">
                    <div className="rounded-xl border border-slate-200/70 bg-white p-4">
                        <div className="flex items-center gap-2 text-sm font-bold text-slate-800">
                            <Terminal className="h-4 w-4 text-slate-500" />
                            {t('guide.cli_title')}
                        </div>
                        <ul className="mt-3 space-y-2 font-mono text-xs text-slate-600">
                            <li>{t('guide.cli_install')}</li>
                            <li>{t('guide.cli_rag')}</li>
                            <li>{t('guide.cli_mcp')}</li>
                        </ul>
                    </div>
                    <div className="rounded-xl border border-slate-200/70 bg-white p-4">
                        <div className="flex items-center gap-2 text-sm font-bold text-slate-800">
                            <BookOpen className="h-4 w-4 text-slate-500" />
                            {t('guide.docs_title')}
                        </div>
                        <p className="mt-3 text-sm text-slate-600">{t('guide.docs_body')}</p>
                        <Link
                            to="/docs"
                            className="mt-3 inline-flex text-sm font-semibold text-brand-700 hover:underline"
                        >
                            {t('docs.open_full_docs')}
                        </Link>
                    </div>
                </div>
            </CardBody>
            )}
        </Card>
    );
}
