import { AnimatePresence, motion } from 'framer-motion';
import {
    Bot,
    BookOpen,
    Braces,
    Code2,
    GitBranch,
    Brain,
    History,
    KeyRound,
    LayoutDashboard,
    Link2,
    Menu,
    MessageSquare,
    PanelsTopLeft,
    Phone,
    Settings,
    Server,
    Sparkles,
    Star,
    Wrench,
    X,
    Zap,
} from 'lucide-react';
import { useState } from 'react';
import { NavLink, Outlet } from 'react-router-dom';
import { useAdminMode } from '../../lib/adminMode';
import { navGroupsForMode, navItemUsesEndMatch } from '../../lib/nav';
import { useI18n } from '../../lib/i18n';
import { AppFooter } from './AppFooter';
import { Breadcrumbs } from './Breadcrumbs';
import { CommandSearch } from './CommandSearch';

const navIcons: Record<string, typeof Bot> = {
    '/': LayoutDashboard,
    '/settings': Settings,
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
    '/widget-settings': MessageSquare,
    '/widget-embed-tokens': KeyRound,
    '/docs': BookOpen,
    '/docs/json-builder': Braces,
    '/custom-code-tools': Code2,
    '/setup': Sparkles,
    '/connections': Link2,
    '/channel-accounts': Phone,
    '/evaluations': Star,
};

function SidebarNav({ onNavigate, groups }: { onNavigate?: () => void; groups: ReturnType<typeof navGroupsForMode> }) {
    const { t } = useI18n();
    return (
        <nav className="flex flex-col gap-5 px-3 py-1">
            {groups.map((group) => (
                <div key={group.labelKey}>
                    <p className="mb-1.5 px-2.5 text-[10px] font-semibold uppercase tracking-[0.14em] text-white/35">{t(group.labelKey)}</p>
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
                                        `relative flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-[13px] font-medium transition ${
                                            isActive ? 'bg-white/10 text-white' : 'text-white/55 hover:bg-white/[0.06] hover:text-white'
                                        }`
                                    }
                                >
                                    {({ isActive }) => (
                                        <>
                                            {isActive && <span className="absolute inset-y-1 start-0 w-0.5 rounded-full bg-brand-400" />}
                                            <Icon className="h-4 w-4 shrink-0 opacity-90" strokeWidth={1.75} />
                                            <span className="truncate">{t(labelKey)}</span>
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

function LocalePills() {
    const { locale, setLocale } = useI18n();
    return (
        <div className="inline-flex rounded-lg border border-slate-200 bg-slate-50 p-0.5">
            {[
                { value: 'en', label: 'EN' },
                { value: 'ar', label: 'ع' },
            ].map((option) => (
                <button
                    key={option.value}
                    type="button"
                    onClick={() => void setLocale(option.value)}
                    className={`rounded-md px-2.5 py-1 text-xs font-semibold transition ${
                        locale === option.value ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-800'
                    }`}
                >
                    {option.label}
                </button>
            ))}
        </div>
    );
}

export function AppShell() {
    const { t, direction } = useI18n();
    const { expertMode, toggleExpertMode } = useAdminMode();
    const navGroups = navGroupsForMode(expertMode);
    const [mobileOpen, setMobileOpen] = useState(false);

    const sidebar = (
        <>
            <div className="flex shrink-0 items-center gap-3 px-4 py-5">
                <div className="flex h-9 w-9 items-center justify-center rounded-xl bg-white text-slate-950 shadow-sm">
                    <Sparkles className="h-4 w-4" />
                </div>
                <div className="min-w-0">
                    <p className="truncate text-sm font-semibold text-white">{t('app_title')}</p>
                    <p className="text-[11px] text-white/40">{t('chrome.workspace')}</p>
                </div>
            </div>
            <div className="ag-scrollbar min-h-0 flex-1 overflow-y-auto overflow-x-hidden pb-4">
                <SidebarNav groups={navGroups} onNavigate={() => setMobileOpen(false)} />
            </div>
            <div className="shrink-0 border-t border-white/8 px-3 py-3">
                <button
                    type="button"
                    onClick={toggleExpertMode}
                    className="flex w-full items-center justify-between rounded-lg px-2.5 py-2 text-left text-xs font-medium text-white/50 transition hover:bg-white/[0.06] hover:text-white"
                >
                    <span>{expertMode ? t('nav.simple_mode') : t('nav.expert_mode')}</span>
                    <span className={`h-5 w-9 rounded-full p-0.5 transition ${expertMode ? 'bg-brand-500' : 'bg-white/15'}`}>
                        <span className={`block h-4 w-4 rounded-full bg-white transition ${expertMode ? 'translate-x-4 rtl:-translate-x-4' : ''}`} />
                    </span>
                </button>
            </div>
        </>
    );

    return (
        <div className="flex min-h-full" dir={direction}>
            <aside className="ag-sidebar-gradient hidden w-64 shrink-0 flex-col border-e border-white/8 lg:sticky lg:top-0 lg:flex lg:h-screen lg:max-h-screen">
                {sidebar}
            </aside>

            <AnimatePresence>
                {mobileOpen && (
                    <motion.div
                        initial={{ opacity: 0 }}
                        animate={{ opacity: 1 }}
                        exit={{ opacity: 0 }}
                        className="fixed inset-0 z-50 bg-slate-950/50 backdrop-blur-sm lg:hidden"
                        onClick={() => setMobileOpen(false)}
                    >
                        <motion.aside
                            initial={{ x: direction === 'rtl' ? 280 : -280 }}
                            animate={{ x: 0 }}
                            exit={{ x: direction === 'rtl' ? 280 : -280 }}
                            transition={{ type: 'spring', damping: 28, stiffness: 320 }}
                            className="ag-sidebar-gradient relative flex h-full max-h-screen w-64 flex-col shadow-2xl"
                            onClick={(e) => e.stopPropagation()}
                        >
                            <button
                                type="button"
                                className="absolute end-3 top-5 z-10 text-white/40 hover:text-white"
                                onClick={() => setMobileOpen(false)}
                            >
                                <X className="h-5 w-5" />
                            </button>
                            {sidebar}
                        </motion.aside>
                    </motion.div>
                )}
            </AnimatePresence>

            <div className="ag-mesh flex min-h-screen min-w-0 flex-1 flex-col">
                <header className="sticky top-0 z-40 border-b border-slate-200/70 bg-white/75 backdrop-blur-xl">
                    <div className="flex items-center gap-3 px-4 py-2.5 sm:px-6 lg:px-8">
                        <button
                            type="button"
                            className="rounded-lg border border-slate-200 bg-white p-2 text-slate-600 lg:hidden"
                            onClick={() => setMobileOpen(true)}
                        >
                            <Menu className="h-5 w-5" />
                        </button>
                        <div className="min-w-0 flex-1">
                            <CommandSearch />
                        </div>
                        <div className="ms-auto flex items-center gap-2">
                            <LocalePills />
                        </div>
                    </div>
                    <div className="border-t border-slate-100/80 px-4 py-1.5 sm:px-6 lg:px-8">
                        <Breadcrumbs />
                    </div>
                </header>

                <main className="w-full flex-1 px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                    <Outlet />
                </main>

                <AppFooter />
            </div>
        </div>
    );
}
