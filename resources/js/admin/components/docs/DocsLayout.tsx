import { ChevronLeft, ChevronRight } from 'lucide-react';
import { Link, NavLink, Outlet, useLocation, useParams } from 'react-router-dom';
import { DOC_PART_LABEL, DOC_PART_ORDER, sortedCatalog } from '../../docs/catalog';
import { useI18n } from '../../lib/i18n';

function DocsSidebar() {
    const { t, direction } = useI18n();
    const { chapterSlug } = useParams();
    const byPart = DOC_PART_ORDER.map((part) => ({
        part,
        items: sortedCatalog().filter((c) => c.part === part),
    }));

    return (
        <aside className="w-full shrink-0 lg:w-64">
            <p className="mb-2 text-xs font-bold uppercase tracking-wide text-slate-500">{t('docs.az_title')}</p>
            <nav className="max-h-[70vh] space-y-4 overflow-y-auto pe-1 lg:max-h-[calc(100vh-8rem)]">
                {byPart.map(({ part, items }) => (
                    <div key={part}>
                        <p className="mb-1 px-2 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                            {t(DOC_PART_LABEL[part])}
                        </p>
                        <ul className="space-y-0.5">
                            {items.map((entry) => (
                                <li key={entry.slug}>
                                    <NavLink
                                        to={`/docs/${entry.slug}`}
                                        className={({ isActive }) =>
                                            `block rounded-lg px-2 py-1.5 text-sm transition ${
                                                isActive || chapterSlug === entry.slug
                                                    ? 'bg-brand-100 font-semibold text-brand-900'
                                                    : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
                                            }`
                                        }
                                    >
                                        {t(`docs.catalog.${entry.labelKey}`)}
                                    </NavLink>
                                </li>
                            ))}
                        </ul>
                    </div>
                ))}
                <div className="border-t border-slate-200 pt-3">
                    <NavLink
                        to="/docs/json-builder"
                        className={({ isActive }) =>
                            `block rounded-lg px-2 py-1.5 text-sm ${isActive ? 'bg-brand-100 font-semibold text-brand-900' : 'text-slate-600 hover:bg-slate-100'}`
                        }
                    >
                        {t('nav.json_builder')}
                    </NavLink>
                </div>
            </nav>
        </aside>
    );
}

function ChapterPager() {
    const { chapterSlug } = useParams();
    const { t, direction } = useI18n();
    if (!chapterSlug) return null;

    const sorted = sortedCatalog();
    const idx = sorted.findIndex((c) => c.slug === chapterSlug);
    if (idx < 0) return null;

    const prev = idx > 0 ? sorted[idx - 1] : null;
    const next = idx < sorted.length - 1 ? sorted[idx + 1] : null;
    const PrevIcon = direction === 'rtl' ? ChevronRight : ChevronLeft;
    const NextIcon = direction === 'rtl' ? ChevronLeft : ChevronRight;

    return (
        <div className="mt-10 flex flex-wrap justify-between gap-3 border-t border-slate-200 pt-6">
            {prev ? (
                <Link to={`/docs/${prev.slug}`} className="inline-flex items-center gap-1 text-sm font-semibold text-brand-700 hover:underline">
                    <PrevIcon className="h-4 w-4" />
                    {t(`docs.catalog.${prev.labelKey}`)}
                </Link>
            ) : (
                <span />
            )}
            {next ? (
                <Link to={`/docs/${next.slug}`} className="inline-flex items-center gap-1 text-sm font-semibold text-brand-700 hover:underline">
                    {t(`docs.catalog.${next.labelKey}`)}
                    <NextIcon className="h-4 w-4" />
                </Link>
            ) : null}
        </div>
    );
}

export function DocsLayout() {
    const { t } = useI18n();
    const location = useLocation();
    const isIndex = location.pathname.replace(/\/$/, '') === '/docs';
    const isJsonBuilder = location.pathname.includes('json-builder');

    return (
        <div className="mx-auto flex w-full max-w-6xl flex-col gap-6 lg:flex-row lg:items-start">
            <DocsSidebar />
            <div className="min-w-0 flex-1">
                {!isIndex && !isJsonBuilder && (
                    <p className="mb-4 text-sm">
                        <Link to="/docs" className="text-brand-700 hover:underline">{t('docs.page_title')}</Link>
                    </p>
                )}
                <Outlet />
                {!isIndex && !isJsonBuilder && <ChapterPager />}
            </div>
        </div>
    );
}
