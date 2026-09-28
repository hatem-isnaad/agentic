import { ChevronRight, Home } from 'lucide-react';
import { Link, useLocation } from 'react-router-dom';
import { catalogBySlug } from '../../docs/catalog';
import { adminNav } from '../../lib/nav';
import { useI18n } from '../../lib/i18n';

export function Breadcrumbs() {
    const { pathname } = useLocation();
    const { t } = useI18n();

    const parts = pathname.split('/').filter(Boolean);
    const crumbs: { href: string; label: string }[] = [{ href: '/', label: t('nav.dashboard') }];

    if (parts.length === 0) {
        return (
            <nav aria-label="Breadcrumb" className="flex items-center gap-1.5 text-sm">
                <span className="font-semibold text-slate-800">{t('nav.dashboard')}</span>
            </nav>
        );
    }

    if (parts[0] === 'docs') {
        crumbs.push({ href: '/docs', label: t('docs.page_title') });
        if (parts[1] === 'json-builder') {
            crumbs.push({ href: '/docs/json-builder', label: t('docs.json_builder_title') });
        } else if (parts[1]) {
            const entry = catalogBySlug(parts[1]);
            crumbs.push({
                href: `/docs/${parts[1]}`,
                label: entry ? t(`docs.catalog.${entry.labelKey}`) : parts[1],
            });
        }
    } else {
        const section = parts[0];
        const navItem = adminNav.find((n) => n.segment === section);
        const sectionHref = navItem?.to ?? `/${section}`;
        crumbs.push({ href: sectionHref, label: navItem ? t(navItem.labelKey) : section });

        if (parts[1] === 'new') {
            crumbs.push({ href: pathname, label: t('actions.create') });
        } else if (parts[1] === 'edit' && parts[2]) {
            crumbs.push({ href: `${sectionHref}/${parts[2]}`, label: parts[2] });
            crumbs.push({ href: pathname, label: t('actions.edit') });
        } else if (parts[1]) {
            crumbs.push({ href: pathname, label: decodeURIComponent(parts[1]) });
        }
    }

    return (
        <nav aria-label="Breadcrumb" className="flex flex-wrap items-center gap-1 text-[13px] text-slate-500">
            {crumbs.map((crumb, i) => {
                const isLast = i === crumbs.length - 1;
                return (
                    <span key={crumb.href + i} className="inline-flex items-center gap-1.5">
                        {i > 0 && <ChevronRight className="h-3.5 w-3.5 shrink-0 text-slate-300" aria-hidden />}
                        {i === 0 ? (
                            <Link to={crumb.href} className="inline-flex items-center gap-1 hover:text-brand-600">
                                <Home className="h-3.5 w-3.5" />
                                <span className={isLast ? 'font-semibold text-slate-800' : ''}>{crumb.label}</span>
                            </Link>
                        ) : isLast ? (
                            <span className="font-semibold text-slate-800">{crumb.label}</span>
                        ) : (
                            <Link to={crumb.href} className="hover:text-brand-600">{crumb.label}</Link>
                        )}
                    </span>
                );
            })}
        </nav>
    );
}
