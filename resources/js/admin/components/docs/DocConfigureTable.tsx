import { useI18n } from '../../lib/i18n';

type Row = {
    taskKey: string;
    cli: string;
    api: string;
    code: string;
};

const ROWS: Row[] = [
    {
        taskKey: 'docs.cfg_install',
        cli: 'agentic:install · migrate · agentic:rag-validate',
        api: '—',
        code: 'config/agentic.php · .env',
    },
    {
        taskKey: 'docs.cfg_code_tools',
        cli: 'agentic:make-code-tool · agentic:code-tools-sync',
        api: 'POST /tools (driver: code)',
        code: 'app/Agentic/Tools/Custom · code_tools in config',
    },
    {
        taskKey: 'docs.cfg_http_tools',
        cli: 'agentic:make connection · agentic:make http-tool',
        api: 'POST /connections · POST /tools (driver: http)',
        code: 'Connection + ToolRepository::save()',
    },
    {
        taskKey: 'docs.cfg_connections',
        cli: 'agentic:make connection · agentic:connection list|refresh',
        api: 'POST /connections · POST /connections/{id}/refresh',
        code: 'ConnectionService · OAuth2TokenManager',
    },
    {
        taskKey: 'docs.cfg_channels',
        cli: 'agentic:make channel-account · agentic:channel-account list',
        api: 'POST /channel-accounts',
        code: 'ChannelAccountService',
    },
    {
        taskKey: 'docs.cfg_evaluations',
        cli: 'agentic:make evaluation · agentic:evaluation list',
        api: 'GET|POST /evaluations',
        code: 'Evaluation model',
    },
    {
        taskKey: 'docs.cfg_mcp',
        cli: 'agentic:mcp-sync {server}',
        api: 'POST /mcp/servers/{server}/sync',
        code: 'config/mcp.php (laravel/mcp)',
    },
    {
        taskKey: 'docs.cfg_skills',
        cli: 'agentic:make skill · agentic:skill list',
        api: 'POST|PUT /skills',
        code: 'SkillRepository::save()',
    },
    {
        taskKey: 'docs.cfg_agents',
        cli: 'agentic:make agent · agentic:agent list',
        api: 'POST|PUT /agents · POST /agents/{slug}/execute',
        code: 'AgentRepository::save()',
    },
    {
        taskKey: 'docs.cfg_knowledge',
        cli: 'agentic:make knowledge · agentic:knowledge ingest',
        api: 'POST /knowledge-sources · …/ingest · …/index',
        code: 'KnowledgeIngestor::ingest()',
    },
    {
        taskKey: 'docs.cfg_widget',
        cli: '—',
        api: 'Widget API: POST /messages · GET /config?agent=',
        code: 'config/agentic.php → widget.*',
    },
    {
        taskKey: 'docs.cfg_widget_embed',
        cli: 'build:widget · publish agentic-widget-assets · widget-embed-token',
        api: 'POST /widget-embed-tokens · Bearer wgt_… on widget API',
        code: '<x-agentic-widget> · AgenticChat.init() · WIDGET_EMBED_SDK.md',
    },
    {
        taskKey: 'docs.cfg_approvals',
        cli: '—',
        api: 'Approval endpoints (widget/runtime)',
        code: 'AGENTIC_TOOL_APPROVAL_* in .env',
    },
    {
        taskKey: 'docs.cfg_memories',
        cli: '—',
        api: 'GET|POST|DELETE /memories',
        code: 'MemoryRepository::remember()',
    },
    {
        taskKey: 'docs.cfg_workflows',
        cli: 'agentic:prune-workflow-runs',
        api: 'POST /workflows · …/execute',
        code: 'WorkflowRepository (package)',
    },
    {
        taskKey: 'docs.cfg_auth',
        cli: '—',
        api: 'Sanctum when AGENTIC_ADMIN_REQUIRE_AUTH',
        code: 'Gate::define(viewAgentic) · admin.web.middleware',
    },
    {
        taskKey: 'docs.cfg_seed',
        cli: 'db:seed --class=…',
        api: '—',
        code: 'Agentic3plFulfillmentSeeder (host)',
    },
];

type Props = { adminApiPrefix: string; widgetApiPrefix?: string };

export function DocConfigureTable({ adminApiPrefix, widgetApiPrefix = '/api/agentic/widget' }: Props) {
    const { t } = useI18n();
    const adminBase = adminApiPrefix.replace(/\/$/, '');
    const widgetBase = widgetApiPrefix.replace(/\/$/, '');

    return (
        <div className="space-y-2">
        <p className="text-xs text-slate-500">
            {t('docs.configure_bases', { admin: adminBase, widget: widgetBase })}
        </p>
        <div className="overflow-x-auto rounded-xl border border-slate-200">
            <table className="w-full min-w-[640px] text-left text-sm">
                <thead>
                    <tr className="border-b border-slate-200 bg-slate-50 text-xs uppercase tracking-wide text-slate-600">
                        <th className="px-4 py-3 font-bold">{t('docs.configure_col_task')}</th>
                        <th className="px-4 py-3 font-bold">{t('docs.configure_col_cli')}</th>
                        <th className="px-4 py-3 font-bold">{t('docs.configure_col_api')}</th>
                        <th className="px-4 py-3 font-bold">{t('docs.configure_col_code')}</th>
                    </tr>
                </thead>
                <tbody>
                    {ROWS.map((row) => (
                        <tr key={row.taskKey} className="border-b border-slate-100 align-top last:border-b-0">
                            <td className="px-4 py-3 font-semibold text-slate-900">{t(row.taskKey)}</td>
                            <td className="px-4 py-3 font-mono text-xs text-slate-700">{row.cli}</td>
                            <td className="px-4 py-3 font-mono text-xs text-slate-700">{row.api}</td>
                            <td className="px-4 py-3 text-xs text-slate-700">{row.code}</td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
        </div>
    );
}
