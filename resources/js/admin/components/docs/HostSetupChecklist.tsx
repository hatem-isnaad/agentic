import { Link } from 'react-router-dom';
import { useI18n } from '../../lib/i18n';
import { Button } from '../ui/Button';
import { Card, CardBody } from '../ui/Card';

const ITEMS = [1, 2, 3, 4, 5] as const;

export function HostSetupChecklist() {
    const { t } = useI18n();

    return (
        <Card className="border-brand-200/70 bg-gradient-to-br from-brand-50/80 to-white">
            <CardBody className="space-y-4">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 className="text-base font-bold text-slate-900">{t('docs.toc_start')}</h2>
                        <p className="mt-1 text-sm text-slate-600">{t('docs.start_subtitle')}</p>
                        <p className="mt-2 text-xs text-slate-500">{t('docs.chapter_code_first_intro')}</p>
                    </div>
                    <Link to="/docs/install">
                        <Button type="button" variant="secondary" className="text-sm">
                            {t('docs.checklist_open_install')}
                        </Button>
                    </Link>
                </div>
                <ol className="space-y-3">
                    {ITEMS.map((n) => (
                        <li
                            key={n}
                            className="flex gap-3 rounded-xl border border-slate-200/80 bg-white px-4 py-3 text-sm shadow-sm"
                        >
                            <span
                                className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-brand-600 text-sm font-bold text-white"
                                aria-hidden
                            >
                                {n}
                            </span>
                            <div className="min-w-0">
                                <p className="font-semibold text-slate-900">{t(`docs.checklist_${n}`)}</p>
                                <p className="mt-1 leading-relaxed text-slate-600">{t(`docs.checklist_${n}_detail`)}</p>
                            </div>
                        </li>
                    ))}
                </ol>
            </CardBody>
        </Card>
    );
}
