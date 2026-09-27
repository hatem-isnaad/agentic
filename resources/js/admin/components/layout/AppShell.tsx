import { AnimatePresence, motion } from 'framer-motion';
import {
    Bot,
    BookOpen,
    Braces,
    Code2,
    GitBranch,
    Brain,
    Cog,
    History,
    LayoutDashboard,
    Menu,
    MessageSquare,
    PanelsTopLeft,
    Settings,
    Server,
    Sparkles,
    Wrench,
    X,
    Zap,
} from 'lucide-react';
import { useState } from 'react';
import { NavLink, Outlet, useLocation } from 'react-router-dom';
import { useAdminMode } from '../../lib/adminMode';
import { navGroupsForMode, navItemUsesEndMatch } from '../../lib/nav';
import { useI18n } from '../../lib/i18n';
import { NativeSelect } from '../ui/NativeSelect';
import { AppFooter } from './AppFooter';
import { Breadcrumbs } from './Breadcrumbs';

const navIcons: Record<string, typeof Bot> = {
    '/': LayoutDashboard,
    '/settings': Cog,
    '/agents': Bot,
    '/memories': Brain,
    '/skills': Zap,
    '/tools': Wrench,
    '/mcp-servers': Server,
    '/knowledge-sources': BookOpen,
    '/workflows': GitBranch,
    '/workflow-runs': History,
    '/executions': PanelsTopLeft,
    '/conversations': MessageSquare,
    '/widget-settings': Settings,
    '/widget-embed-tokens': Settings,
    '/docs': BookOpen,
    '/docs/json-builder': Braces,
    '/custom-code-tools': Code2,
    '/setup': Sparkles,
};

function SidebarNav({ onNavigate, groups }: { onNavigate?: () => void; groups: ReturnType<typeof navGroupsForMode> }) {
    const { t } = useI18n();
    return (
        <nav className="flex flex-col gap-4 px-3 py-2">
            {groups.map((group) => (
                <div key={group.labelKey}>
                    <p className="mb-1 px-3 text-[10px] font-bold uppercase tracking-wider text-slate-500">{t(group.labelKey)}</p>
                    <div className="flex flex-col gap-0.5">
                        {group.items.map(({ to, labelKey }) => {
                            const Icon = navIcons[to] ?? LayoutDashboard;
                            return (
                                <NavLink
                                    key={to}
                                    to={to}
                                    end={navItemUsesEndMatch(to)}
                                    onClick={onNavigate}
                                    className={({ isActive }) =>
                                        `group relative flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition-all duration-200 ${
                                            isActive
                                                ? 'bg-white/12 text-white shadow-inner'
                                                : 'text-slate-400 hover:bg-white/6 hover:text-white'
                                        }`
                                    }
                                >
                                    {({ isActive }) => (
                                        <>
                                            {isActive && (
                                                <motion.span
                                                    layoutId={`nav-pill-${to}`}
                                                    className="absolute inset-0 rounded-xl bg-gradient-to-r from-brand-500/25 to-violet-500/20"
                                                    transition={{ type: 'spring', stiffness: 380, damping: 32 }}
                                                />
                                            )}
                                            <Icon className="relative z-10 h-[18px] w-[18px] shrink-0 opacity-95" strokeWidth={2} />
                                            <span className="relative z-10">{t(labelKey)}</span>
                                        </>
                                    )}
                                </NavLink>
                            );
                        })}
                    </div>
                </div>
            ))}
        </nav>
    );
}

export function AppShell() {
    const { t, locale, setLocale, direction } = useI18n();
    const { expertMode, toggleExpertMode } = useAdminMode();
    const navGroups = navGroupsForMode(expertMode);
    const [mobileOpen, setMobileOpen] = useState(false);
    const location = useLocation();

    const localeOptions = [
        { value: 'en', label: t('locale.en') },
        { value: 'ar', label: t('locale.ar') },
    ];

    return (
        <div className="flex min-h-full" dir={direction}>
            <aside className="ag-sidebar-gradient hidden w-[17.5rem] shrink-0 flex-col border-r border-white/5 lg:sticky lg:top-0 lg:flex lg:h-screen lg:max-h-screen">
                <div className="flex shrink-0 items-center gap-3 px-5 py-6">
                    <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-brand-500 to-violet-600 text-white shadow-lg shadow-brand-500/30">
                        <Sparkles className="h-5 w-5" />
                    </div>
                    <div>
                        <p className="text-sm font-bold text-white">{t('app_title')}</p>
                        <p className="text-xs text-slate-500">Agentic</p>
                    </div>
                </div>
                <div className="min-h-0 flex-1 overflow-y-auto overflow-x-hidden overscroll-y-contain">
                    <SidebarNav groups={navGroups} />
                </div>
                <div className="shrink-0 border-t border-white/10 px-4 py-4">
                    <button
                        type="button"
                        onClick={toggleExpertMode}
                        className="w-full rounded-xl px-3 py-2.5 text-left text-xs font-semibold text-slate-400 transition hover:bg-white/6 hover:text-white"
                    >
                        {expertMode ? t('nav.simple_mode') : t('nav.expert_mode')}
                    </button>
                </div>
            </aside>

            <AnimatePresence>
                {mobileOpen && (
                    <motion.div
                        initial={{ opacity: 0 }}
                        animate={{ opacity: 1 }}
                        exit={{ opacity: 0 }}
                        className="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm lg:hidden"
                        onClick={() => setMobileOpen(false)}
                    >
                        <motion.aside
                            initial={{ x: direction === 'rtl' ? 280 : -280 }}
                            animate={{ x: 0 }}
                            exit={{ x: direction === 'rtl' ? 280 : -280 }}
                            transition={{ type: 'spring', damping: 28, stiffness: 320 }}
                            className="ag-sidebar-gradient flex h-full max-h-screen w-[17.5rem] flex-col shadow-2xl"
                            onClick={(e) => e.stopPropagation()}
                        >
                            <div className="flex items-center justify-between px-5 py-5">
                                <span className="font-bold text-white">{t('app_title')}</span>
                                <button type="button" className="text-slate-400" onClick={() => setMobileOpen(false)}>
                                    <X className="h-5 w-5" />
                                </button>
                            </div>
                            <div className="min-h-0 flex-1 overflow-y-auto">
                                <SidebarNav groups={navGroups} onNavigate={() => setMobileOpen(false)} />
                            </div>
                            <div className="shrink-0 border-t border-white/10 px-4 py-4">
                                <button
                                    type="button"
                                    onClick={toggleExpertMode}
                                    className="w-full rounded-xl px-3 py-2 text-left text-xs font-semibold text-slate-400"
                                >
                                    {expertMode ? t('nav.simple_mode') : t('nav.expert_mode')}
                                </button>
                            </div>
                        </motion.aside>
                    </motion.div>
                )}
            </AnimatePresence>

            <div className="ag-mesh flex min-w-0 flex-1 flex-col min-h-screen">
                <header className="sticky top-0 z-40 border-b border-slate-200/60 bg-white/80 backdrop-blur-xl">
                    <div className="flex items-center justify-between gap-4 px-4 py-3 sm:px-8 lg:px-10">
                        <button
                            type="button"
                            className="rounded-xl border border-slate-200 bg-white p-2 text-slate-600 shadow-sm lg:hidden"
                            onClick={() => setMobileOpen(true)}
                        >
                            <Menu className="h-5 w-5" />
                        </button>
                        <div className="hidden flex-1 lg:block">
                            <Breadcrumbs />
                        </div>
                        <div className="w-44 shrink-0">
                            <NativeSelect
                                value={locale}
                                onValueChange={setLocale}
                                options={localeOptions}
                                placeholder={t('locale.label')}
                            />
                        </div>
                    </div>
                    <div className="border-t border-slate-100 px-4 py-2 lg:hidden">
                        <Breadcrumbs />
                    </div>
                </header>

                <main className="flex-1 w-full px-4 py-6 sm:px-8 lg:px-10 lg:py-8">
                    <AnimatePresence mode="wait">
                        <motion.div
                            key={location.pathname}
                            className="w-full"
                            initial={{ opacity: 0, y: 10 }}
                            animate={{ opacity: 1, y: 0 }}
                            exit={{ opacity: 0, y: -6 }}
                            transition={{ duration: 0.25, ease: [0.22, 1, 0.36, 1] }}
                        >
                            <Outlet />
                        </motion.div>
                    </AnimatePresence>
                </main>

                <AppFooter />
            </div>
        </div>
    );
}
