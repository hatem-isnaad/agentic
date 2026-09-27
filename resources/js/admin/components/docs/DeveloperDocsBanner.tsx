import { Code2, Terminal } from 'lucide-react';
import { useI18n } from '../../lib/i18n';

export function DeveloperDocsBanner() {
    const { t } = useI18n();

    return (
        <div className="rounded-xl border border-slate-200 bg-slate-900 px-5 py-4 text-slate-100 shadow-sm">
            <div className="flex flex-wrap items-start gap-4">
                <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-brand-600/90 text-white">
                    <Code2 className="h-5 w-5" strokeWidth={2} aria-hidden />
                </div>
                <div className="min-w-0 flex-1 space-y-2">
                    <p className="text-sm font-bold tracking-tight">{t('docs.developer_first_title')}</p>
                    <p className="text-sm leading-relaxed text-slate-300">{t('docs.developer_first_body')}</p>
                    <ul className="flex flex-wrap gap-x-4 gap-y-1 text-xs font-medium text-slate-400">
                        <li className="inline-flex items-center gap-1.5">
                            <Terminal className="h-3.5 w-3.5 text-brand-400" aria-hidden />
                            {t('docs.developer_path_cli')}
                        </li>
                        <li>{t('docs.developer_path_api')}</li>
                        <li>{t('docs.developer_path_php')}</li>
                    </ul>
                </div>
            </div>
        </div>
    );
}
