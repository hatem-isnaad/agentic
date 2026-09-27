import type { DocChapterMap } from './types';

/** Full A→Z developer guide (English). Arabic: chapters/ar.ts */
export const chaptersEn: DocChapterMap = {
    install: {
        title: '01 · Install Agentic',
        summary: 'Add the package to Laravel, publish config, migrate, set AI keys, validate.',
        goal: 'A working Agentic install you can configure from the terminal and .env — the admin UI is optional.',
        steps: [
            {
                title: 'One Composer require',
                body:
                    'Run this in your Laravel app root (not inside the package folder). Agentic declares laravel/ai in its own composer.json, so the Laravel AI SDK is installed transitively — never add laravel/ai as a second require.',
            },
            {
                title: 'Publish Agentic + Laravel AI config',
                body:
                    'agentic:install publishes config/agentic.php. Publishing ai-config creates config/ai.php for provider API keys (OpenAI, Anthropic, Gemini, Ollama).',
            },
            {
                title: 'Database tables',
                body:
                    'migrate creates agents, skills, tools, knowledge, conversations, executions, and related tables. Use AGENTIC_*_DRIVER=memory in tests if you prefer no DB.',
            },
            {
                title: 'Admin SPA assets (optional)',
                body:
                    'Only if you want the debug SPA at /agentic/admin. Publish compiled assets to public/vendor/agentic — not required for runtime API, widget, or repository-based setup.',
            },
            {
                title: 'Environment & smoke tests',
                body:
                    'Copy keys from vendor/hatem-isnaad/agentic/.env.example. Use rag-validate --offline first (no API calls), then run without --offline once AGENTIC_AI_PROVIDER and keys (or Ollama) work.',
            },
        ],
        commands: [
            {
                command: 'composer require hatem-isnaad/agentic',
                title: 'Install the package',
                why: 'Registers the service provider, autoloads Agentic, and pulls in laravel/ai automatically.',
                note: 'Path repo? Add a repositories entry in composer.json, then require hatem-isnaad/agentic:@dev',
            },
            {
                command: 'php artisan agentic:install',
                title: 'Publish Agentic configuration',
                why: 'Creates config/agentic.php so your host can override drivers, code tool paths, widget URLs, and feature flags.',
            },
            {
                command: 'php artisan vendor:publish --tag=ai-config',
                title: 'Publish Laravel AI SDK config',
                why: 'Creates config/ai.php — map OPENAI_API_KEY, ANTHROPIC_API_KEY, GEMINI_API_KEY, OLLAMA_URL, etc.',
            },
            {
                command: 'php artisan migrate',
                title: 'Run migrations',
                why: 'Persists agents, skills, tools, knowledge sources, conversations, and execution history in your database.',
            },
            {
                command: 'php artisan vendor:publish --tag=agentic-admin-assets --force',
                title: 'Publish admin UI assets (optional)',
                why: 'Enables the React admin SPA for visual inspection — all resources can be created via API/PHP without this step.',
            },
            {
                command: 'php artisan agentic:rag-validate --offline',
                title: 'Smoke-test RAG without AI APIs',
                why: 'Uses deterministic embeddings to verify vector store wiring — safe on CI and laptops without keys.',
            },
            {
                command: 'php artisan agentic:rag-validate',
                title: 'Smoke-test RAG with real embeddings',
                why: 'Calls your configured embedding provider (AGENTIC_KNOWLEDGE_EMBEDDING=laravel_ai) — run after .env keys work.',
            },
        ],
        env: [
            { key: 'AGENTIC_ENABLED', description: 'Master switch (default true)' },
            { key: 'AGENTIC_AI_PROVIDER', description: 'Default provider when an agent omits one: openai | gemini | anthropic | ollama' },
            { key: 'AGENTIC_AI_MODEL', description: 'Default model slug for new agents' },
            { key: 'OPENAI_API_KEY', description: 'Laravel AI / OpenAI (see also config/ai.php)' },
            { key: 'OLLAMA_URL', description: 'Base URL when using local Ollama (e.g. http://localhost:11434)' },
        ],
        envExample: `# Minimal .env after copying vendor/hatem-isnaad/agentic/.env.example
AGENTIC_AI_PROVIDER=ollama
AGENTIC_AI_MODEL=qwen3.5:4b
OLLAMA_URL=http://localhost:11434

# Or cloud:
# AGENTIC_AI_PROVIDER=openai
# AGENTIC_AI_MODEL=gpt-4.1-mini
# OPENAI_API_KEY=sk-...`,
        cli: `# Run from your Laravel project root
composer require hatem-isnaad/agentic
php artisan agentic:install
php artisan vendor:publish --tag=ai-config
php artisan migrate
php artisan vendor:publish --tag=agentic-admin-assets --force

# Copy AI / Agentic keys from vendor/hatem-isnaad/agentic/.env.example → .env
php artisan agentic:rag-validate --offline
php artisan agentic:rag-validate`,
        php: `// config/agentic.php (excerpt) — custom PHP tools auto-discovered from your app
'code_tools' => [
    'auto_register' => true,
    'paths' => [app_path('Agentic/Tools/Custom')],
    'namespace' => 'App\\\\Agentic\\\\Tools\\\\Custom',
],`,
        adminNote:
            'Canonical host docs: vendor/hatem-isnaad/agentic/docs/DEVELOPER_QUICKSTART.md and CONFIGURE_BY_CODE.md',
    },
    environment: {
        title: '02 · Environment & config file',
        summary: 'Every .env knob maps to config/agentic.php — change both places consciously.',
        goal: 'Know where to toggle features without hunting the codebase.',
        steps: [
            { title: 'Published config', body: 'config/agentic.php after agentic:install — host overrides win.' },
            { title: 'Clear config cache', body: 'php artisan config:clear after .env changes.' },
            { title: 'Drivers', body: 'execution, conversation, knowledge, memory, workflow drivers: eloquent vs memory (tests).' },
            { title: 'Feature flags', body: 'admin.enabled, widget.enabled, api.enabled, workflows.enabled, memory.enabled.' },
        ],
        configPaths: ['config/agentic.php', '.env'],
        env: [
            { key: 'AGENTIC_ENABLED', description: 'Disable entire package' },
            { key: 'AGENTIC_ADMIN_ENABLED', description: 'Admin API + optional SPA' },
            { key: 'AGENTIC_ADMIN_WEB_ENABLED', description: 'Serve /agentic/admin SPA' },
            { key: 'AGENTIC_WIDGET_ENABLED', description: 'Widget JSON API' },
            { key: 'AGENTIC_API_ENABLED', description: 'Runtime JSON API under /api/agentic' },
        ],
        api: 'GET /settings — effective config snapshot (no secrets)',
        ui: { path: '/settings', label: 'Package settings' },
    },
    ai: {
        title: '03 · AI provider & models',
        summary: 'Laravel AI SDK providers; defaults for agents without provider/model.',
        goal: 'Configure Ollama locally or cloud keys in production.',
        steps: [
            { title: 'Set defaults', body: 'AGENTIC_AI_PROVIDER + AGENTIC_AI_MODEL in .env' },
            { title: 'Allowed models list', body: 'config agentic.ai.providers.*.models — admin AI registry reads this.' },
            { title: 'Per-agent override', body: 'Set provider + model on each agent record (API or AgentRepository).' },
            { title: 'Deferred tools (optional)', body: 'AGENTIC_DEFERRED_TOOLS for large tool catalogs.' },
        ],
        env: [
            { key: 'AGENTIC_AI_PROVIDER', description: 'openai | gemini | anthropic | ollama' },
            { key: 'AGENTIC_AI_MODEL', description: 'Default model slug' },
            { key: 'AGENTIC_DEFERRED_TOOLS', description: 'Lazy-load tool definitions' },
            { key: 'OLLAMA_URL', description: 'Host Ollama base URL when using ollama' },
        ],
        api: 'GET /ai-registry — providers/models for forms',
        php: 'Agent payload: provider, model, instructions, temperature, max_tokens in config JSON',
    },
    'code-tools': {
        title: '04 · Custom PHP (code) tools',
        summary: 'Business logic in your app — auto-discovered handlers.',
        goal: 'Ship CheckStock-style tools without manual registry boilerplate.',
        steps: [
            { title: 'Configure paths', body: "code_tools.paths + namespace in config/agentic.php" },
            { title: 'Generate class', body: 'php artisan agentic:make-code-tool YourTool' },
            { title: 'Implement handler', body: 'DeclarativeCodeToolHandler — metadata() + handle()' },
            { title: 'Sync DB', body: 'php artisan agentic:code-tools-sync' },
            { title: 'Publish tool record', body: 'POST /tools driver code OR POST /code-handlers/publish' },
            { title: 'Attach to skill → agent', body: 'Include tool slug on skill and agent' },
        ],
        env: [{ key: 'AGENTIC_CODE_TOOLS_AUTO_REGISTER', description: 'Register handlers on boot (default true)' }],
        cli: `php artisan agentic:make-code-tool CheckStock
php artisan agentic:code-tools-sync`,
        api: `GET /code-handlers
POST /code-handlers/sync
POST /code-handlers/publish
POST /tools  { "driver": "code", "definition": { "handler": "..." } }`,
        php: 'app/Agentic/Tools/Custom/*.php · ToolRepository::save()',
        ui: { path: '/custom-code-tools', label: 'Custom PHP tools' },
    },
    'http-tools': {
        title: '05 · HTTP tools',
        summary: 'Call REST APIs with JSON schema validation.',
        goal: 'Expose external APIs to agents safely.',
        steps: [
            { title: 'Define tool', body: 'method, url with {params}, input_schema JSON Schema' },
            { title: 'Publish', body: 'status published + publish:true on create' },
            { title: 'Permissions', body: 'Agent tool_permissions patterns if using permission checker' },
        ],
        api: `POST /tools
{
  "name": "Get order",
  "slug": "get-order",
  "driver": "http",
  "status": "published",
  "publish": true,
  "definition": {
    "method": "GET",
    "url": "https://api.example.com/orders/{order_id}",
    "input_schema": { "type": "object", "properties": { "order_id": { "type": "string" } }, "required": ["order_id"] }
  }
}`,
        ui: { path: '/tools/new', label: 'New HTTP tool' },
    },
    mcp: {
        title: '06 · MCP tools',
        summary: 'Sync tools from laravel/mcp servers.',
        goal: 'Reuse MCP ecosystem tools inside agents.',
        steps: [
            { title: 'Configure servers', body: 'Host config/mcp.php (laravel/mcp)' },
            { title: 'Sync', body: 'php artisan agentic:mcp-sync {server}' },
            { title: 'Attach synced tools', body: 'Add tool slugs to skills like any other tool' },
        ],
        env: [
            { key: 'AGENTIC_MCP_ENABLED', description: 'Enable MCP integration' },
            { key: 'AGENTIC_MCP_TOOL_PREFIX', description: 'Prefix synced tool slugs' },
            { key: 'AGENTIC_MCP_INJECT_RESOURCES', description: 'Inject MCP resources into context' },
        ],
        cli: 'php artisan agentic:mcp-sync my-server',
        api: 'GET /mcp/servers · POST /mcp/servers/{server}/sync · GET .../tools',
        ui: { path: '/mcp-servers', label: 'MCP servers' },
    },
    skills: {
        title: '07 · Skills',
        summary: 'Bundle tools + knowledge for agents.',
        goal: 'Reusable capability packages.',
        steps: [
            { title: 'Create skill', body: 'name, slug, tools[], knowledge[], instructions optional' },
            { title: 'Publish', body: 'status published' },
            { title: 'Link to agent', body: 'agent.skills array of slugs' },
        ],
        api: `POST /skills
{ "name": "Ops", "slug": "ops", "status": "published", "tools": ["check-stock"], "knowledge": ["kb-ops"] }`,
        php: 'SkillRepository::save()',
        ui: { path: '/skills/new', label: 'New skill' },
    },
    agents: {
        title: '08 · Agents',
        summary: 'Persona + model + skills + tool permissions.',
        goal: 'Published agent ready for widget or execute API.',
        steps: [
            { title: 'Basics', body: 'name, slug, instructions, status published' },
            { title: 'AI', body: 'provider + model from registry' },
            { title: 'Attach skills', body: 'skills: ["my-skill"]' },
            { title: 'Test', body: 'POST /agents/{slug}/execute { "message": "hi" }' },
        ],
        api: `POST /agents
POST /agents/{slug}/execute`,
        php: 'AgentRepository::save()',
        ui: { path: '/agents/new', label: 'New agent' },
    },
    knowledge: {
        title: '09 · Knowledge (RAG)',
        summary: 'Vector store, ingest, reindex, search.',
        goal: 'Grounded answers from your documents.',
        steps: [
            { title: 'Create source', body: 'driver vector, slug, published' },
            { title: 'Configure embeddings', body: 'AGENTIC_KNOWLEDGE_EMBEDDING*, AGENTIC_VECTOR_STORE' },
            { title: 'Ingest', body: 'POST .../ingest text, urls, or PDF multipart' },
            { title: 'Reindex', body: 'POST .../index or ingest with reindex' },
            { title: 'Attach to skill', body: 'knowledge slugs on skill' },
        ],
        env: [
            { key: 'AGENTIC_VECTOR_STORE', description: 'array | pgvector | pinecone' },
            { key: 'AGENTIC_KNOWLEDGE_EMBEDDING', description: 'laravel_ai | null (offline)' },
            { key: 'AGENTIC_PGVECTOR_DIMENSIONS', description: 'Vector dimensions' },
            { key: 'AGENTIC_KNOWLEDGE_QUEUE_REINDEX', description: 'Queue reindex jobs' },
        ],
        cli: 'php artisan agentic:rag-validate [--offline]',
        api: 'POST /knowledge-sources · POST .../ingest · POST .../search',
        ui: { path: '/knowledge-sources/new', label: 'New knowledge source' },
    },
    memories: {
        title: '10 · Memories',
        summary: 'Scoped facts injected into agent context.',
        goal: 'Tenant/user/agent/conversation scoped memory.',
        steps: [
            { title: 'Enable', body: 'AGENTIC_MEMORY_ENABLED=true' },
            { title: 'CRUD via API', body: 'scope, scope_key, key, content, importance' },
            { title: 'Runtime injection', body: 'MemoryManager loads by scope when agent runs' },
        ],
        env: [
            { key: 'AGENTIC_MEMORY_ENABLED', description: 'Toggle memory feature' },
            { key: 'AGENTIC_MEMORY_MAX_CONTEXT_ENTRIES', description: 'Cap injected memories' },
        ],
        api: 'GET|POST /memories · DELETE /memories/{id}',
        ui: { path: '/memories', label: 'Memories' },
    },
    permissions: {
        title: '11 · Tool permissions',
        summary: 'Allow/deny tool execution by pattern before drivers run.',
        goal: 'Lock down which tools an deployment may call.',
        steps: [
            { title: 'Default policy', body: 'AGENTIC_PERMISSION_DEFAULT deny|allow' },
            { title: 'Checker class', body: 'AGENTIC_PERMISSION_CHECKER — use RuleBasedPermissionChecker in prod' },
            { title: 'Patterns', body: 'AGENTIC_PERMISSION_ALLOW_PATTERNS / DENY_PATTERNS' },
            { title: 'Per agent', body: 'agent permissions field — comma patterns in admin' },
        ],
        env: [
            { key: 'AGENTIC_PERMISSION_DEFAULT', description: 'deny (secure) or allow' },
            { key: 'AGENTIC_PERMISSION_ALLOW_PATTERNS', description: 'e.g. orders.*,kb.*' },
        ],
        php: 'Bind custom PermissionChecker in service provider',
    },
    approvals: {
        title: '12 · Human tool approval',
        summary: 'Pause dangerous tools until a human approves.',
        goal: 'Human-in-the-loop for writes/deletes.',
        steps: [
            { title: 'Enable patterns', body: 'AGENTIC_TOOL_APPROVAL_ENABLED + PATTERNS' },
            { title: 'Per-tool default', body: 'approvals.default on tool definition' },
            { title: 'Widget flow', body: 'User sees approval UI; auto_execute_on_approve resumes agent' },
        ],
        env: [
            { key: 'AGENTIC_TOOL_APPROVAL_ENABLED', description: 'Master toggle' },
            { key: 'AGENTIC_TOOL_APPROVAL_PATTERNS', description: '*.write,*.delete,...' },
            { key: 'AGENTIC_TOOL_APPROVAL_AUTO_EXECUTE', description: 'Run tool after approve' },
        ],
    },
    'skill-routing': {
        title: '13 · Skill routing',
        summary: 'Pick subset of skills per message automatically.',
        goal: 'Multi-skill agents without sending all tools every turn.',
        steps: [
            { title: 'Enable routing', body: 'AGENTIC_SKILL_ROUTING=true, limit N skills' },
            { title: 'AI routing (optional)', body: 'AGENTIC_AI_SKILL_ROUTING + provider/model' },
        ],
        env: [
            { key: 'AGENTIC_SKILL_ROUTING', description: 'Enable skill router' },
            { key: 'AGENTIC_SKILL_ROUTING_LIMIT', description: 'Max skills per turn' },
        ],
    },
    widget: {
        title: '14 · Widget API (HTTP)',
        summary: 'Headless chat API for guests and authenticated users — use from your app or the embed SDK.',
        goal: 'Integrate chat without the admin UI; all traffic is JSON under /api/agentic/widget.',
        steps: [
            { title: 'Enable API', body: 'AGENTIC_WIDGET_ENABLED=true' },
            { title: 'Identity', body: 'X-Agentic-Guest-Id per browser, or Sanctum user when AGENTIC_WIDGET_AUTH_MODE allows auth' },
            { title: 'Send', body: 'POST /messages with agent, message, optional conversation_id' },
            { title: 'Embed UI', body: 'See chapter 15 — standalone agentic-widget.js popup (recommended for Blade/SPA)' },
        ],
        env: [
            { key: 'AGENTIC_WIDGET_AUTH_MODE', description: 'guest | auth | both' },
            { key: 'AGENTIC_WIDGET_MAX_CONVERSATIONS', description: 'Open chats per user' },
        ],
        api: `POST /messages
GET /config?agent=slug
GET /conversations/{id}/messages
GET /conversations/{id}/realtime?since_id=`,
        adminNote: 'Full embed guide: vendor/hatem-isnaad/agentic/docs/WIDGET_EMBED_SDK.md',
    },
    'widget-embed': {
        title: '15 · Widget embed SDK (popup)',
        summary:
            'Paste embed on your site — JS/CSS is public; API is locked with wgt_… secret + allowed domains so copies on other sites fail.',
        goal:
            'Give visitors a popup chat on only your domains: publish assets, create an embed token with origin allowlist, inject the secret from the server, enable require_token in production.',
        steps: [
            {
                title: 'Build & publish assets',
                body:
                    'From Laravel app root: npm run build:widget (host delegates to vendor/hatem-isnaad/agentic). First time: cd vendor/hatem-isnaad/agentic && npm install. Then php artisan vendor:publish --tag=agentic-widget-assets --force. Output: public/vendor/agentic/widget/agentic-widget.js + .css (no separate HTML — UI is built in JS).',
            },
            {
                title: 'Download JS/CSS for external sites',
                body:
                    'curl or copy the two files from https://YOUR-HOST/vendor/agentic/widget/. Self-host on CDN/S3 or link directly to Laravel. Re-download after each widget build. Optional: AGENTIC_WIDGET_EMBED_SCRIPT_URL for CDN base URL in Blade.',
                code: `curl -O https://YOUR-HOST/vendor/agentic/widget/agentic-widget.js
curl -O https://YOUR-HOST/vendor/agentic/widget/agentic-widget.css`,
            },
            {
                title: 'External site embed (full HTML)',
                body:
                    'Page on www.example.com; apiBase points to Laravel; token origins must list www.example.com. Configure CORS on Laravel if API host ≠ page host.',
                code: `<link rel="stylesheet" href="https://API-HOST/vendor/agentic/widget/agentic-widget.css">
<script src="https://API-HOST/vendor/agentic/widget/agentic-widget.js" defer></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    AgenticChat.init({
      agent: 'support',
      apiBase: 'https://API-HOST/api/agentic/widget',
      embedToken: 'INJECT_wgt_FROM_SERVER',
      theme: 'system',
      position: 'bottom-right',
    });
  });
</script>`,
            },
            {
                title: 'Create embed secret + domain lock',
                body:
                    'Admin → Embed tokens or CLI. Set allowed_origins to your real site URLs (https://www.example.com). The wgt_… value is your embed API secret (not a separate public key). Shown once — put in .env, never commit.',
            },
            {
                title: 'Enforce in production',
                body:
                    'AGENTIC_WIDGET_EMBED_REQUIRE_TOKEN=true — widget API returns 401 without Bearer wgt_… or Sanctum. Origin must match the token allowlist or you get “Origin not allowed”.',
            },
            {
                title: 'Copy-paste embed (Blade)',
                body: 'Recommended: server injects token so static HTML repos never contain wgt_…',
                code: `<x-agentic-widget
  agent="support"
  :token="config('services.agentic.widget_embed_token')"
  theme="system"
  position="bottom-right"
/>`,
            },
            {
                title: 'Copy-paste embed (any site)',
                body: 'Load CSS/JS from your Laravel host; set embedToken from backend-rendered config only',
                code: `<link rel="stylesheet" href="/vendor/agentic/widget/agentic-widget.css">
<script src="/vendor/agentic/widget/agentic-widget.js" defer></script>
<script>
  AgenticChat.init({
    agent: 'support',
    apiBase: '/api/agentic/widget',
    embedToken: 'FROM_SERVER_ENV',
    theme: 'system',
    position: 'bottom-right',
  });
</script>`,
            },
            {
                title: 'What strangers cannot do',
                body:
                    'They may download agentic-widget.js, but API calls from another domain fail (origin check). Stolen wgt_… does not work off your allowlist. Restrict allowed_agents so tokens cannot drive other agents.',
            },
            {
                title: 'Theme & realtime',
                body:
                    'Per-agent theme via PUT /widget-settings/{agent}. Pusher: AGENTIC_WIDGET_BROADCAST_DRIVER=pusher + queue worker. See WIDGET_EMBED_SDK.md § External sites.',
            },
        ],
        commands: [
            {
                command: 'npm run build:widget',
                title: 'Compile embed bundle',
                why: 'Outputs resources/dist/widget/agentic-widget.js and .css',
            },
            {
                command: 'php artisan vendor:publish --tag=agentic-widget-assets --force',
                title: 'Copy to public/vendor/agentic/widget',
                why: 'Host serves static assets for <script src="...">',
            },
            {
                command: 'php artisan agentic:widget-embed-token create --name=prod --agents=support --origins=https://app.example.com',
                title: 'Create embed token (CLI)',
                why: 'Same as admin Embed tokens page; plain token shown once',
            },
        ],
        env: [
            { key: 'AGENTIC_WIDGET_EMBED_REQUIRE_TOKEN', description: 'Require wgt_… or Sanctum on all widget API calls' },
            { key: 'AGENTIC_WIDGET_EMBED_DEFAULT_AGENT', description: 'Default agent for Blade component' },
            { key: 'AGENTIC_WIDGET_EMBED_POSITION', description: 'bottom-right | bottom-left | top-right | top-left' },
            { key: 'AGENTIC_WIDGET_EMBED_SCRIPT_URL', description: 'Optional CDN base for widget JS/CSS (no trailing slash)' },
            { key: 'AGENTIC_WIDGET_SOUND_PACK', description: 'subtle built-in UI sounds in embed client' },
        ],
        envExample: `# Production
AGENTIC_WIDGET_EMBED_REQUIRE_TOKEN=true
AGENTIC_WIDGET_EMBED_DEFAULT_AGENT=support`,
        cli: `# Publish assets
php artisan vendor:publish --tag=agentic-widget-assets --force

# Token (or use Admin → Embed tokens)
php artisan agentic:widget-embed-token create --name=production --agents=support`,
        php: `// .env: AGENTIC_WIDGET_EMBED_TOKEN=wgt_… (from token create)
// config/services.php → agentic.widget_embed_token

<x-agentic-widget
    agent="support"
    :token="config('services.agentic.widget_embed_token')"
    theme="system"
    position="bottom-right"
/>`,
        api: `POST /widget-embed-tokens
Authorization: Bearer wgt_…
X-Agentic-Guest-Id: {uuid}

POST /messages { "agent": "support", "message": "Hello" }`,
        adminNote:
            'Canonical reference: vendor/hatem-isnaad/agentic/docs/WIDGET_EMBED_SDK.md · Admin: /agentic/admin/widget-embed-tokens',
        ui: { path: '/widget-embed-tokens', label: 'Embed tokens (required for secure production)' },
    },
    'widget-settings': {
        title: '16 · Per-agent widget overrides',
        summary: 'Welcome, intake, theme, locale per agent.',
        goal: 'Customize chat UX without forking the widget.',
        steps: [
            { title: 'Global defaults', body: 'config agentic.widget.* and .env AGENTIC_WIDGET_*' },
            { title: 'Per agent', body: 'PUT /widget-settings/{agentSlug}' },
            { title: 'Reset', body: 'DELETE /widget-settings/{agentSlug}' },
        ],
        api: 'GET /widget-settings/schema · PUT /widget-settings/{agent}',
        ui: { path: '/widget-settings', label: 'Widget settings' },
    },
    workflows: {
        title: '17 · Workflows',
        summary: 'Multi-step automation with runs and resume.',
        goal: 'Orchestrate agents/tools beyond single chat turns.',
        steps: [
            { title: 'Enable', body: 'AGENTIC_WORKFLOWS_ENABLED' },
            { title: 'Define steps JSON', body: 'POST /workflows with steps array' },
            { title: 'Execute', body: 'POST /workflows/{slug}/execute' },
            { title: 'Prune old runs', body: 'php artisan agentic:prune-workflow-runs' },
        ],
        env: [
            { key: 'AGENTIC_WORKFLOW_MAX_STEPS', description: 'Safety cap' },
            { key: 'AGENTIC_WORKFLOW_RUN_RETENTION_DAYS', description: 'Retention for prune command' },
        ],
        api: 'POST /workflows/{slug}/execute · POST .../resume',
        ui: { path: '/workflows/new', label: 'New workflow' },
    },
    'runtime-api': {
        title: '18 · Runtime API (headless)',
        summary: 'Same CRUD as admin under /api/agentic when enabled.',
        goal: 'Automate from your backend without admin prefix.',
        steps: [
            { title: 'Enable', body: 'AGENTIC_API_ENABLED=true' },
            { title: 'Auth', body: 'AGENTIC_API_REQUIRE_AUTH + Sanctum tokens' },
            { title: 'Rate limit', body: 'AGENTIC_API_RATE_LIMIT_*' },
        ],
        env: [
            { key: 'AGENTIC_API_ENABLED', description: 'Register runtime routes' },
            { key: 'AGENTIC_API_REQUIRE_AUTH', description: 'auth:sanctum on runtime API' },
        ],
        api: 'Prefix: /api/agentic — agents, tools, workflows, memories, mcp, ...',
    },
    'auth-admin': {
        title: '19 · Admin auth & gates',
        summary: 'Open by default; lock with Sanctum + Gate.',
        goal: 'Production-safe admin SPA and API.',
        steps: [
            { title: 'API tokens', body: 'AGENTIC_ADMIN_REQUIRE_AUTH=true' },
            { title: 'SPA session', body: "Add 'auth' to admin.web.middleware" },
            { title: 'Telescope gate', body: 'AGENTIC_ADMIN_GATE=viewAgentic + Gate::define(..., $user=null)' },
            { title: 'Passkeys (optional)', body: 'laravel/passkeys on /api/agentic/auth routes' },
        ],
        env: [
            { key: 'AGENTIC_ADMIN_REQUIRE_AUTH', description: 'Sanctum on admin API' },
            { key: 'AGENTIC_ADMIN_GATE', description: 'Laravel gate name' },
        ],
        php: "Gate::define('viewAgentic', fn ($user = null) => ...);",
    },
    tenant: {
        title: '20 · Multi-tenant',
        summary: 'Resolve tenant from header or user attribute.',
        goal: 'Isolate data per tenant in host app.',
        steps: [
            { title: 'Header', body: 'X-Agentic-Tenant-Id (configurable)' },
            { title: 'User attribute', body: 'AGENTIC_TENANT_USER_ATTRIBUTE' },
            { title: 'Custom resolver', body: 'Bind Agentic\\Contracts\\TenantResolver' },
        ],
        env: [
            { key: 'AGENTIC_TENANT_HEADER', description: 'HTTP header name' },
            { key: 'AGENTIC_TENANT_USER_ATTRIBUTE', description: 'User model column' },
        ],
    },
    'http-security': {
        title: '21 · HTTP tool & ingest security',
        summary: 'SSRF protection for HTTP tools and URL ingest.',
        goal: 'Prevent agents from hitting internal networks.',
        steps: [
            { title: 'Keep defaults in prod', body: 'allow_private_hosts false, allow_redirects false' },
            { title: 'Size limits', body: 'AGENTIC_HTTP_MAX_RESPONSE_BYTES' },
        ],
        env: [
            { key: 'AGENTIC_HTTP_ALLOW_PRIVATE_HOSTS', description: 'Never true in prod unless trusted' },
            { key: 'AGENTIC_HTTP_ALLOW_REDIRECTS', description: 'Redirect following for tools' },
        ],
    },
    'drivers-storage': {
        title: '22 · Drivers & storage',
        summary: 'Swap eloquent vs memory drivers for tests.',
        goal: 'Know which driver backs each resource.',
        steps: [
            { title: 'Execution', body: 'AGENTIC_EXECUTION_DRIVER' },
            { title: 'Conversation', body: 'AGENTIC_CONVERSATION_DRIVER' },
            { title: 'Knowledge catalog', body: 'AGENTIC_KNOWLEDGE_DRIVER' },
            { title: 'Memory', body: 'AGENTIC_MEMORY_DRIVER' },
            { title: 'Workflows', body: 'AGENTIC_WORKFLOW_DRIVER' },
        ],
        env: [
            { key: 'AGENTIC_EXECUTION_DRIVER', description: 'eloquent | memory' },
            { key: 'AGENTIC_CONVERSATION_DRIVER', description: 'eloquent | memory' },
        ],
    },
    monitoring: {
        title: '23 · Executions & conversations',
        summary: 'Inspect runs and chat history.',
        goal: 'Debug agent behavior in production.',
        steps: [
            { title: 'Executions', body: 'GET /executions — each agent run with trace' },
            { title: 'Conversations', body: 'GET /conversations?agent=slug filter' },
            { title: 'Messages', body: 'GET /conversations/{id}/messages' },
        ],
        api: 'GET /executions · GET /conversations · GET /conversations/{id}/messages',
        ui: { path: '/executions', label: 'Executions' },
    },
    seeding: {
        title: '24 · Seeders & demos',
        summary: 'Repeatable full stacks in code.',
        goal: 'CI/staging environments with one command.',
        steps: [
            { title: 'Host seeder', body: 'Agentic3plFulfillmentSeeder in laravel-host example' },
            { title: 'Run', body: 'php artisan db:seed --class=Agentic3plFulfillmentSeeder' },
            { title: 'Repositories', body: 'AgentRepository, ToolRepository, KnowledgeIngestor, MemoryRepository' },
        ],
        cli: 'php artisan db:seed --class=YourAgenticSeeder',
        php: 'database/seeders/Agentic3plFulfillmentSeeder.php',
    },
    artisan: {
        title: '25 · Artisan command reference',
        summary: 'All package CLI entry points.',
        goal: 'Quick lookup without opening README.',
        steps: [
            { title: 'agentic:install', body: 'Publish config, print next steps' },
            { title: 'agentic:make-code-tool', body: 'Scaffold PHP handler class' },
            { title: 'agentic:code-tools-sync', body: 'Register + sync tool records' },
            { title: 'agentic:mcp-sync', body: 'Import MCP tools' },
            { title: 'agentic:rag-validate', body: 'Test embeddings + vector store' },
            { title: 'agentic:prune-workflow-runs', body: 'Delete old workflow runs' },
            { title: 'agentic:widget-embed-token', body: 'Create/list/revoke wgt_… embed secrets' },
        ],
        cli: `php artisan agentic:install
php artisan agentic:make-code-tool Name
php artisan agentic:code-tools-sync
php artisan agentic:mcp-sync
php artisan agentic:rag-validate
php artisan agentic:widget-embed-token create --name=prod
php artisan agentic:prune-workflow-runs`,
    },
};
