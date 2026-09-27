import { useEffect, useState } from 'react';
import { useI18n } from '../../lib/i18n';

export type TocGroup = {
    label: string;
    items: { id: string; label: string }[];
};

type Props = { groups: TocGroup[] };

export function DocToc({ groups }: Props) {
    const { t } = useI18n();
    const [active, setActive] = useState<string>('');

    useEffect(() => {
        const ids = groups.flatMap((g) => g.items.map((i) => i.id));
        const elements = ids.map((id) => document.getElementById(id)).filter(Boolean) as HTMLElement[];

        if (elements.length === 0) {
            return;
        }

        const observer = new IntersectionObserver(
            (entries) => {
                const visible = entries.filter((e) => e.isIntersecting).sort((a, b) => b.intersectionRatio - a.intersectionRatio);
                if (visible[0]?.target.id) {
                    setActive(visible[0].target.id);
                }
            },
            { rootMargin: '-20% 0px -55% 0px', threshold: [0, 0.25, 0.5] },
        );

        elements.forEach((el) => observer.observe(el));

        return () => observer.disconnect();
    }, [groups]);

    return (
        <nav className="lg:sticky lg:top-6 lg:max-h-[calc(100vh-3rem)] lg:overflow-y-auto">
            <p className="mb-3 text-xs font-bold uppercase tracking-wide text-slate-500">{t('docs.toc_title')}</p>
            <div className="space-y-5">
                {groups.map((group) => (
                    <div key={group.label}>
                        <p className="mb-2 text-xs font-semibold text-slate-800">{group.label}</p>
                        <ul className="space-y-1 border-s border-slate-200 ps-3">
                            {group.items.map((item) => (
                                <li key={item.id}>
                                    <a
                                        href={`#${item.id}`}
                                        className={`block rounded-md py-1.5 pe-2 text-sm transition-colors ${
                                            active === item.id
                                                ? 'font-semibold text-brand-700 bg-brand-50'
                                                : 'text-slate-600 hover:text-brand-700'
                                        }`}
                                    >
                                        {item.label}
                                    </a>
                                </li>
                            ))}
                        </ul>
                    </div>
                ))}
            </div>
        </nav>
    );
}
