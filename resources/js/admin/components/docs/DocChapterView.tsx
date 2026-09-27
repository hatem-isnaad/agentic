import { Link } from 'react-router-dom';
import type { DocChapterContent } from '../../docs/chapters/types';
import { useI18n } from '../../lib/i18n';
import { CodeBlock } from './CodeBlock';
import { DeveloperDocsBanner } from './DeveloperDocsBanner';
import { Button } from '../ui/Button';

type Props = { chapter: DocChapterContent; adminApiPrefix: string };

function expandApiPaths(api: string, apiBase: string): string {
    return api
        .replace(/POST \/tools/g, `POST ${apiBase}/tools`)
        .replace(/GET \/tools/g, `GET ${apiBase}/tools`);
}

export function DocChapterView({ chapter, adminApiPrefix }: Props) {
    const { t } = useI18n();
    const apiBase = adminApiPrefix.replace(/\/$/, '');
    const hasCode =
        (chapter.commands?.length ?? 0) > 0 ||
        !!chapter.cli ||
        !!chapter.envExample ||
        !!chapter.php ||
        !!chapter.api;

    return (
        <article className="docs-prose space-y-10">
            <header className="space-y-3 border-b border-slate-200/80 pb-8">
                <h1 className="docs-h1">{chapter.title}</h1>
                <p className="docs-lead">{chapter.summary}</p>
                <p className="text-sm text-slate-600">
                    <span className="font-semibold text-slate-800">{t('docs.chapter_goal')}: </span>
                    {chapter.goal}
                </p>
            </header>

            <DeveloperDocsBanner />

            {hasCode && (
                <section className="space-y-6">
                    <div>
                        <h2 className="docs-h2">{t('docs.chapter_code_first')}</h2>
                        <p className="mt-2 text-sm text-slate-600">{t('docs.chapter_code_first_intro')}</p>
                    </div>

                    {chapter.commands && chapter.commands.length > 0 && (
                        <div className="space-y-5">
                            <h3 className="text-sm font-bold uppercase tracking-wide text-slate-500">
                                {t('docs.chapter_commands')}
                            </h3>
                            <ol className="space-y-5">
                                {chapter.commands.map((cmd, i) => (
                                    <li key={i} className="docs-step-card">
                                        <span className="docs-step-num">{i + 1}</span>
                                        <div className="min-w-0 flex-1 space-y-3">
                                            <div>
                                                <p className="font-semibold text-slate-900">{cmd.title}</p>
                                                <p className="mt-1 text-sm text-slate-600">{cmd.why}</p>
                                                {cmd.note ? (
                                                    <p className="mt-2 text-xs text-slate-500">{cmd.note}</p>
                                                ) : null}
                                            </div>
                                            <CodeBlock title={t('docs.install_terminal')} code={cmd.command} />
                                        </div>
                                    </li>
                                ))}
                            </ol>
                        </div>
                    )}

                    {chapter.cli && <CodeBlock title={t('docs.chapter_cli_all')} code={chapter.cli} />}
                    {chapter.envExample && (
                        <CodeBlock title={t('docs.chapter_env_example')} code={chapter.envExample} />
                    )}
                    {chapter.php && <CodeBlock title={t('docs.chapter_php')} code={chapter.php} />}
                    {chapter.api && (
                        <CodeBlock
                            title={`${t('docs.chapter_api')} (${apiBase})`}
                            code={expandApiPaths(chapter.api, apiBase)}
                        />
                    )}
                </section>
            )}

            {chapter.env && chapter.env.length > 0 && (
                <section>
                    <h2 className="mb-3 text-sm font-bold uppercase tracking-wide text-slate-500">
                        {t('docs.chapter_env')}
                    </h2>
                    <ul className="docs-env-table">
                        {chapter.env.map((row) => (
                            <li key={row.key}>
                                <code className="docs-env-key">{row.key}</code>
                                <span className="text-slate-600">{row.description}</span>
                            </li>
                        ))}
                    </ul>
                </section>
            )}

            {chapter.configPaths && (
                <section>
                    <h2 className="mb-2 text-sm font-bold uppercase tracking-wide text-slate-500">
                        {t('docs.chapter_config')}
                    </h2>
                    <ul className="list-disc space-y-1 ps-5 text-sm text-slate-700">
                        {chapter.configPaths.map((p) => (
                            <li key={p}>
                                <code className="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-xs text-rose-800">
                                    {p}
                                </code>
                            </li>
                        ))}
                    </ul>
                </section>
            )}

            {chapter.steps.length > 0 && (
                <section>
                    <h2 className="mb-3 text-sm font-bold uppercase tracking-wide text-slate-500">
                        {t('docs.chapter_concepts')}
                    </h2>
                    <p className="mb-4 text-sm text-slate-600">{t('docs.chapter_concepts_intro')}</p>
                    <ol className="space-y-3">
                        {chapter.steps.map((step, i) => (
                            <li key={i} className="docs-step-card">
                                <span className="docs-step-num">{i + 1}</span>
                                <div className="min-w-0 flex-1">
                                    <p className="font-semibold text-slate-900">{step.title}</p>
                                    <p className="mt-1 whitespace-pre-line text-slate-600">{step.body}</p>
                                    {step.code ? (
                                        <div className="mt-3">
                                            <CodeBlock title={t('docs.install_terminal')} code={step.code} />
                                        </div>
                                    ) : null}
                                </div>
                            </li>
                        ))}
                    </ol>
                </section>
            )}

            {chapter.adminNote && (
                <p className="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                    {chapter.adminNote}
                </p>
            )}

            {chapter.ui && (
                <aside className="rounded-xl border border-dashed border-slate-300 bg-slate-50/80 px-4 py-4">
                    <p className="text-xs font-bold uppercase tracking-wide text-slate-500">
                        {t('docs.chapter_ui_section')}
                    </p>
                    <p className="mt-2 text-sm text-slate-600">{t('docs.chapter_ui_optional')}</p>
                    <Link to={chapter.ui.path} className="mt-3 inline-block">
                        <Button type="button" variant="secondary" className="text-sm">
                            {chapter.ui.label}
                        </Button>
                    </Link>
                </aside>
            )}
        </article>
    );
}
