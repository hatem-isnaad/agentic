import { useI18n } from '../../lib/i18n';

export function AppFooter() {
    const { t } = useI18n();
    const year = new Date().getFullYear();

    return (
        <footer className="mt-auto border-t border-slate-200/70 bg-white/50 px-4 py-4 backdrop-blur sm:px-6 lg:px-8">
            <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <p className="text-xs text-slate-500">
                    © {year} {t('app_title')}. {t('footer.rights')}
                </p>
                <p className="text-xs text-slate-400">{t('footer.tagline')}</p>
            </div>
        </footer>
    );
}
