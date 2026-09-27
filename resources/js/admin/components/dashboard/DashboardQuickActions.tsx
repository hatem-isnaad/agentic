import { Bot, BookOpen, MessageSquare, Settings, Sparkles, Wrench, Zap } from 'lucide-react';
import { Link } from 'react-router-dom';
import { useAdminMode } from '../../lib/adminMode';
import { useAdminConfig } from '../../lib/config';
import { useI18n } from '../../lib/i18n';
import { Card, CardBody } from '../ui/Card';

export function DashboardQuickActions() {
    const { t } = useI18n();
    const { expertMode } = useAdminMode();
    const boot = useAdminConfig();
    const widgetHref = boot.webPrefix.replace(/\/admin\/?$/, '/widget') + '?agent=inbound-receiving';

    if (!expertMode) {
        return (
            <Card className="mb-6 border-brand-200/50 bg-gradient-to-br from-brand-50/80 to-white">
                <CardBody className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 className="text-lg font-bold text-slate-900">{t('simple.hero_title')}</h2>
                        <p className="mt-1 text-sm text-slate-600">{t('simple.hero_body')}</p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Link
                            to="/setup"
                            className="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-5 py-3 text-sm font-semibold text-white shadow-md hover:bg-brand-700"
                        >
                            <Sparkles className="h-4 w-4" />
                            {t('nav.setup_wizard')}
                        </Link>
                        <Link
                            to="/agents"
                            className="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-800 hover:bg-slate-50"
                        >
                            <Bot className="h-4 w-4" />
                            {t('nav.agents')}
                        </Link>
                        <a
                            href={widgetHref}
                            target="_blank"
                            rel="noreferrer"
                            className="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-800 hover:bg-slate-50"
                        >
                            <MessageSquare className="h-4 w-4" />
                            {t('quick.open_widget')}
                        </a>
                    </div>
                </CardBody>
            </Card>
        );
    }

    const actions = [
        { to: '/setup', icon: Sparkles, titleKey: 'nav.setup_wizard', descKey: 'setup.subtitle' },
        { to: '/agents/new', icon: Bot, titleKey: 'quick.new_agent', descKey: 'quick.new_agent_desc' },
        { to: '/skills/new', icon: Zap, titleKey: 'quick.new_skill', descKey: 'quick.new_skill_desc' },
        { to: '/tools/new', icon: Wrench, titleKey: 'quick.new_tool', descKey: 'quick.new_tool_desc' },
        { to: '/knowledge-sources/new', icon: BookOpen, titleKey: 'quick.new_kb', descKey: 'quick.new_kb_desc' },
        { to: '/widget-settings', icon: MessageSquare, titleKey: 'quick.widget', descKey: 'quick.widget_desc' },
        { to: '/settings', icon: Settings, titleKey: 'quick.settings', descKey: 'quick.settings_desc' },
    ];

    return (
        <Card className="mb-6 border-brand-200/40">
            <CardBody>
                <h2 className="text-lg font-bold text-slate-900">{t('quick.title')}</h2>
                <p className="mt-1 text-sm text-slate-600">{t('quick.subtitle')}</p>
                <div className="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    {actions.map(({ to, icon: Icon, titleKey, descKey }) => (
                        <Link
                            key={to}
                            to={to}
                            className="flex gap-3 rounded-xl border border-slate-200/90 bg-white p-4 shadow-sm transition hover:border-brand-300 hover:shadow-md"
                        >
                            <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-100 text-brand-700">
                                <Icon className="h-5 w-5" />
                            </div>
                            <div>
                                <p className="font-semibold text-slate-900">{t(titleKey)}</p>
                                <p className="mt-0.5 text-xs leading-relaxed text-slate-600">{t(descKey)}</p>
                            </div>
                        </Link>
                    ))}
                </div>
            </CardBody>
        </Card>
    );
}
