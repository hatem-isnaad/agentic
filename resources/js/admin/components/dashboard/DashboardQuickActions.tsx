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
            <Card className="mb-6">
                <CardBody className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 className="text-base font-semibold text-slate-950">{t('simple.hero_title')}</h2>
                        <p className="mt-1 text-sm text-slate-500">{t('simple.hero_body')}</p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Link
                            to="/setup"
                            className="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800"
                        >
                            <Sparkles className="h-4 w-4" />
                            {t('nav.setup_wizard')}
                        </Link>
                        <Link
                            to="/agents"
                            className="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50"
                        >
                            <Bot className="h-4 w-4" />
                            {t('nav.agents')}
                        </Link>
                        <a
                            href={widgetHref}
                            target="_blank"
                            rel="noreferrer"
                            className="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50"
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
        <Card className="mb-6">
            <CardBody>
                <h2 className="text-base font-semibold text-slate-950">{t('quick.title')}</h2>
                <p className="mt-1 text-sm text-slate-500">{t('quick.subtitle')}</p>
                <div className="mt-4 grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
                    {actions.map(({ to, icon: Icon, titleKey, descKey }) => (
                        <Link
                            key={to}
                            to={to}
                            className="flex gap-3 rounded-xl border border-transparent p-3 transition hover:border-slate-200 hover:bg-slate-50"
                        >
                            <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-700">
                                <Icon className="h-4 w-4" strokeWidth={1.75} />
                            </div>
                            <div>
                                <p className="text-sm font-semibold text-slate-900">{t(titleKey)}</p>
                                <p className="mt-0.5 text-xs leading-relaxed text-slate-500">{t(descKey)}</p>
                            </div>
                        </Link>
                    ))}
                </div>
            </CardBody>
        </Card>
    );
}
