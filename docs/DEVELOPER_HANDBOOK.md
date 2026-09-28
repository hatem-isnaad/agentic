# Developer handbook

**One place for `.env`.** Do not copy env lists from the README or other docs — they all point here.

Words, **fresh vs finished app**: [START_HERE.md](./START_HERE.md). Widget install: [WIDGET_EMBED_SDK.md](./WIDGET_EMBED_SDK.md).

---

## Starter set (copy this first)

**Easiest:** run `php artisan agentic:install` and answer the wizard (choices + secrets → `.env`). See [INSTALL_WIZARD.md](./INSTALL_WIZARD.md).

**Or paste manually** — one deployment switch plus AI keys:

```env
AGENTIC_MODE=local
AGENTIC_ENABLED=true
AGENTIC_AI_PROVIDER=openai
AGENTIC_AI_MODEL=gpt-4.1-mini
OPENAI_API_KEY=

AGENTIC_ADMIN_WEB_ENABLED=true
AGENTIC_PERMISSION_DEFAULT=deny
# AGENTIC_PERMISSION_ALLOW_PATTERNS=orders.*,crm.*

AGENTIC_WIDGET_ENABLED=true
AGENTIC_WIDGET_BROADCAST_DRIVER=pusher
PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_APP_CLUSTER=mt1

# Live server only:
# AGENTIC_ADMIN_GATE=viewAgentic
```

Then:

```bash
php artisan migrate
php artisan config:clear
php artisan queue:work
```

Open `/agentic/admin`. Create tool → skill → knowledge (optional) → published agent. Create a `wgt_…` token and drop the widget on a page.

Need a different provider? Anthropic: `AGENTIC_AI_PROVIDER=anthropic`, `AGENTIC_AI_MODEL=claude-sonnet-5`, `ANTHROPIC_API_KEY=`. Gemini: `gemini` + `GEMINI_API_KEY`. Local: `ollama` + `OLLAMA_URL` + `ollama pull`.

Need document search later? See **Knowledge** below. Need cheaper or richer replies? See **Tokens** below.

---

## How tokens work

Each user message builds a **prompt**: instructions + persona + some old chat + some tools + some knowledge chunks + some memories. The provider bills (and slows down) on that prompt size.

| You raise… | Tokens | Typical effect |
|------------|--------|----------------|
| History (turns) | **High** | Each extra turn is often 50–400 tokens |
| Tools | **High** | Each tool schema is often 80–400 tokens |
| Knowledge chunks | **High** | Each chunk ≈ `CHUNK_SIZE` characters (~¼ as tokens) |
| Memories | **Low–medium** | Short facts; 10 rows is usually cheap |
| Skills (descriptions) | **Medium** | Compact mode cuts this |
| Extra LLM routing | **High** | A second full model call every turn |

Widget keys (`AGENTIC_WIDGET_*`) are **stricter** than the global ones. The widget uses widget limits when `channel=widget`. Defaults are already a good cost/quality balance. Only change them if replies miss context (raise a little) or bills/speed hurt (lower).

After any `.env` edit: `php artisan config:clear` and `php artisan queue:restart`.

---

## Every key (this is the catalog)

### Core

| Key | Default | What it does | Small / large | Tokens |
|-----|---------|--------------|---------------|--------|
| `AGENTIC_ENABLED` | `true` | Master on/off | `false` = package does nothing | — |
| `AGENTIC_AI_PROVIDER` | — | Who runs the model if the agent has no override | Must match a working SDK provider | — |
| `AGENTIC_AI_MODEL` | — | Which model if the agent has no override | Must exist on that provider. Bigger models cost more per token | Price per token changes with the model, not this name |
| `AGENTIC_OPENAI_MODELS` | `gpt-4.1-mini,gpt-4o` | Admin dropdown only | Longer list = more choices in UI. Does not install models | — |
| `AGENTIC_ANTHROPIC_MODELS` | `claude-sonnet-5,claude-sonnet-4-6` | Same | Same | — |
| `AGENTIC_GEMINI_MODELS` | `gemini-3.6-flash,…` | Same | Same | — |
| `AGENTIC_OLLAMA_MODELS` | `qwen3.5:4b,llama3.2` | Same | You must `ollama pull` each name | — |
| `AGENTIC_FALLBACK_AGENT` | empty | Agent slug if routing finds none | Leave empty unless you have a catch-all | — |

Set provider + model on the **agent** in admin when you want a different brain per agent.

### Tokens and context (global)

Used for admin execute / runtime. Widget uses the widget table unless lean is off.

| Key | Default | What it does | Small / large | Tokens |
|-----|---------|--------------|---------------|--------|
| `AGENTIC_LEAN_CONTEXT` | `true` | Cap history, skills, tools, RAG, memory | `false` = send much more of everything. Slower, often 2–5× prompt size | **Huge** if `false` |
| `AGENTIC_CONTEXT_HISTORY` | `12` | How many older turns go into the prompt | `8` = cheaper, forgets earlier chat. `40` = remembers more, costs more | **High** — linear with turns |
| `AGENTIC_CONTEXT_SKILL_LIMIT` | `4` | Skills after a keyword match | Higher = more tool groups in the prompt | **Medium** |
| `AGENTIC_CONTEXT_SKILLS_FALLBACK` | `4` | Skills when no keyword matches | Same | **Medium** |
| `AGENTIC_CONTEXT_KNOWLEDGE_LIMIT` | `3` | RAG chunks this turn | `2` = may miss the answer. `12` = better recall, fatter prompt | **High** |
| `AGENTIC_CONTEXT_MEMORY_LIMIT` | `8` | Memory rows this turn | Higher = more facts, modest cost | **Low–medium** |
| `AGENTIC_CONTEXT_MAX_TOOLS` | `30` | Tools registered this turn | `10` = model sees fewer actions. `80` = large tool JSON | **High** |
| `AGENTIC_CONTEXT_COMPACT_SKILLS` | `true` | Shorten skill text | `false` = full descriptions | **Medium** if `false` |
| `AGENTIC_COMPACT_INPUT` | `true` | Trim history, tool dumps, RAG, memory. **Does not hide tools** | `false` = full API JSON + long history text | **Huge** if `false` |
| `AGENTIC_COMPACT_HISTORY_CHARS` | `700` | Max chars per older history turn | Raise if the model “forgets” mid-thread facts | **Medium** |
| `AGENTIC_COMPACT_HISTORY_RECENT_CHARS` | `1400` | Max chars for the last 2 turns | Raise if the last answer is a long table | **Medium** |
| `AGENTIC_COMPACT_HISTORY_TOTAL` | `3600` | Max chars of all history combined | Oldest turns drop first | **High** |
| `AGENTIC_COMPACT_TOOL_RESULT_CHARS` | `1800` | Max chars of a tool result sent back to the model | Raise for huge invoices; default is enough for one order | **High** |

### Tokens and context (widget only)

Same idea, tighter defaults. Raise only if the popup “forgets” or cannot use a tool it should have.

| Key | Default | Small / large | Tokens |
|-----|---------|---------------|--------|
| `AGENTIC_WIDGET_LEAN_CONTEXT` | `true` | `false` = widget as fat as global | **Huge** if `false` |
| `AGENTIC_WIDGET_CONTEXT_HISTORY` | `8` | `6` cheaper; `24` longer memory in the popup | **High** |
| `AGENTIC_WIDGET_SKILL_LIMIT` | `2` | Higher = more skill blurbs in the widget | **Medium** |
| `AGENTIC_WIDGET_SKILLS_FALLBACK_LIMIT` | `2` | Same when no keyword | **Medium** |
| `AGENTIC_WIDGET_KNOWLEDGE_LIMIT` | `3` | Higher = more FAQ/policy text per question | **High** |
| `AGENTIC_WIDGET_MEMORY_LIMIT` | `5` | Higher = more saved facts | **Low–medium** |
| `AGENTIC_WIDGET_MAX_TOOLS` | `20` | Higher = more tool schemas in the widget turn | **High** |
| `AGENTIC_WIDGET_COMPACT_SKILLS` | `true` | `false` = longer skill text | **Medium** if `false` |

### Skill routing (which tools this turn)

| Key | Default | What it does | Small / large | Tokens |
|-----|---------|--------------|---------------|--------|
| `AGENTIC_SKILL_ROUTING` | `true` | Pick skills by keywords | `false` = more skills dumped in | **Higher** if `false` |
| `AGENTIC_SKILL_ROUTING_LIMIT` | `4` | Max skills when keywords match | Higher = more groups | **Medium** |
| `AGENTIC_SKILL_ROUTING_FALLBACK_LIMIT` | `4` | Max when none match | Same | **Medium** |
| `AGENTIC_AI_SKILL_ROUTING` | `false` | Extra model call to classify the question | `true` = smarter pick, **one more paid call every turn** | **High** (extra request) |
| `AGENTIC_AI_SKILL_ROUTING_PROVIDER` | same as AI | Who classifies | Must have a key | — |
| `AGENTIC_AI_SKILL_ROUTING_MODEL` | same as AI | Which model classifies | Keep a live model | Extra call uses this model’s price |
| `AGENTIC_AI_SKILL_ROUTING_MIN_CONFIDENCE` | `0.75` | Ignore weak picks | Lower = more routing; higher = more fallback | — |
| `AGENTIC_DEFERRED_TOOLS` | `true` | When the agent has more tools than `AGENTIC_DEFERRED_TOOL_COUNT`, OpenAI/Anthropic get a search wrapper instead of every schema | `false` = always send every tool schema | **High** if `false` |
| `AGENTIC_DEFERRED_TOOL_COUNT` | `32` | Tool count that still stays fully visible | Raise if order/SKU lookups never actually run | **Medium** |
| `AGENTIC_DIRECT_TOOL_COUNT` | `8` | Schemas that stay visible after search starts | Anthropic needs more than 1 | **Low–medium** |

### Permissions and approvals (not tokens)

| Key | Default | What it does | Small / large |
|-----|---------|--------------|---------------|
| `AGENTIC_PERMISSION_DEFAULT` | `deny` | Tools blocked unless allowed | `allow` is easy locally, unsafe on the internet |
| `AGENTIC_PERMISSION_CHECKER` | deny-all class | Use `Agentic\Permission\RuleBasedPermissionChecker` in production | — |
| `AGENTIC_PERMISSION_ALLOW_PATTERNS` | empty | e.g. `orders.*,crm.*` | Broader = more tools may run |
| `AGENTIC_PERMISSION_DENY_PATTERNS` | empty | e.g. `*.delete` | Broader = more tools blocked |
| `AGENTIC_PERMISSION_DENIAL_MESSAGE` | `Permission denied…` | Shown when blocked | — |
| `AGENTIC_TOOL_APPROVAL_ENABLED` | `true` | Pause write-like tools for a human | `false` = writes run at once |
| `AGENTIC_TOOL_APPROVAL_PATTERNS` | `*.write,*.create,…` | Which names need a click | Wider = more pauses |
| `AGENTIC_TOOL_APPROVAL_AUTO_EXECUTE` | `true` | Run after approve | `false` = approve only |
| `AGENTIC_TOOL_APPROVAL_AUTO_RESUME` | `true` | Continue the chat after run | `false` = user must send again |

### Persistence

`memory` is for tests. Production: `eloquent` + `migrate`. Does not change tokens.

| Key | Default |
|-----|---------|
| `AGENTIC_EXECUTION_DRIVER` | `eloquent` |
| `AGENTIC_CONVERSATION_DRIVER` | `eloquent` |
| `AGENTIC_KNOWLEDGE_DRIVER` | `eloquent` |
| `AGENTIC_MEMORY_DRIVER` | `eloquent` |
| `AGENTIC_WORKFLOW_DRIVER` | `eloquent` |

### Knowledge / search

| Key | Default | What it does | Small / large | Tokens |
|-----|---------|--------------|---------------|--------|
| `AGENTIC_KNOWLEDGE_EMBEDDING` | `null` | `null` = no search. `laravel_ai` = real vectors | Must be `laravel_ai` for RAG | Embeddings cost on **ingest** and **each question** |
| `AGENTIC_KNOWLEDGE_EMBEDDING_PROVIDER` | same as AI | Who embeds | Must work | — |
| `AGENTIC_KNOWLEDGE_EMBEDDING_MODEL` | provider default | Embedding model | **Must match vector size** or search is wrong | — |
| `AGENTIC_VECTOR_STORE` | `array` | `array` = RAM only. `postgres` / `pgvector` / `pinecone` for real | `array` is not for production | — |
| `AGENTIC_PGVECTOR_DIMENSIONS` | `1536` | Stored vector length | e.g. `1024` for `mxbai-embed-large` | — |
| `AGENTIC_PINECONE_DIMENSIONS` | same | Pinecone index size | Same rule | — |
| `PINECONE_HOST` / `PINECONE_API_KEY` | empty | Hosted index | Required if store is pinecone | — |
| `OLLAMA_EMBEDDING_MODEL` | `mxbai-embed-large` | When embed provider is Ollama | Match dimensions | — |
| `AGENTIC_KNOWLEDGE_CHUNK_SIZE` | `800` | Characters per stored piece | **Smaller** (400) = more pieces, finer search, more embed calls. **Larger** (2000) = fewer pieces, worse recall, each hit costs more tokens | **High** per hit if large |
| `AGENTIC_KNOWLEDGE_CHUNK_OVERLAP` | `120` | Repeat between pieces | Higher = more stored text and embed cost | Storage, not the chat prompt |
| `AGENTIC_KNOWLEDGE_QUEUE_REINDEX` | `false` | Reindex on the queue | `true` needs `queue:work` | — |
| `AGENTIC_KNOWLEDGE_URL_FETCH` | `true` | Fetch HTTPS pages on ingest | `false` = paste only | — |
| `AGENTIC_KNOWLEDGE_URL_FETCH_TIMEOUT` | `15` | Seconds | Higher = slower ingest | — |
| `AGENTIC_KNOWLEDGE_URL_FETCH_MAX_BYTES` | 5MB | Max download | Higher = larger pages | — |
| `AGENTIC_RAG_LOG_QUERIES` | `false` | Log the user query text | Keep `false` — queries can hold names, phones, orders | — |
| `AGENTIC_RAG_LOG_EMBEDDINGS` | `false` | Log vector previews only | Debug only | — |

After you change embed model or dimensions, **reindex** every source and run `php artisan agentic:rag-validate`.

### Widget chat (behavior, not prompt size)

| Key | Default | What it does | Small / large |
|-----|---------|--------------|---------------|
| `AGENTIC_WIDGET_ENABLED` | `true` | Widget API | `false` = embed dies |
| `AGENTIC_WIDGET_PREFIX` | `api/agentic/widget` | API path | Must match JS `apiBase` |
| `AGENTIC_WIDGET_WEB_ENABLED` | `true` | Demo page `/agentic/widget` | — |
| `AGENTIC_WIDGET_WEB_PREFIX` | `agentic/widget` | Demo path | — |
| `AGENTIC_WIDGET_AUTH_MODE` | `both` | `guest` / `auth` / `both` | — |
| `AGENTIC_WIDGET_ALLOW_GUEST` | `true` | Guest header | `false` = login only |
| `AGENTIC_WIDGET_ALLOW_AUTH` | `true` | Logged-in Sanctum users | — |
| `AGENTIC_WIDGET_MAX_CONVERSATIONS` | `20` | Inbox length | Higher = longer list (HTTP, not model tokens) |
| `AGENTIC_WIDGET_RESUME_HOURS` | `24` | Reuse last thread if newer | Higher = fewer auto-new chats |
| `AGENTIC_WIDGET_HISTORY_PAGE_SIZE` | `20` | Messages loaded per scroll page | Higher = heavier first paint, not the LLM prompt |
| `AGENTIC_WIDGET_HISTORY_MAX_PAGE_SIZE` | `50` | Max `?limit=` | Clients cannot exceed this |
| `AGENTIC_WIDGET_WELCOME_MESSAGE` | empty | First line | Longer text is only UI |
| `AGENTIC_WIDGET_LOCALE` | `en` | Default locale | — |
| `AGENTIC_WIDGET_THEME` | `light` | Fallback theme | Use `isnaad`, `techsup`, … in `init` |
| `AGENTIC_WIDGET_THEME_DIRECTION` | `auto` | `ltr` / `rtl` / `auto` | — |
| `AGENTIC_WIDGET_EMBED_REQUIRE_TOKEN` | `true` | Require `wgt_…` | `false` only on localhost |
| `AGENTIC_WIDGET_EMBED_TOKEN` | empty | `wgt_…` injected on `/agentic/widget` | Create with `agentic:widget-embed-token` |
| `AGENTIC_WIDGET_EMBED_POSITION` | `bottom-right` | `bottom-left` / `top-*` | — |
| `AGENTIC_WIDGET_BROADCAST_DRIVER` | `polling` | Use `pusher` for live replies | — |
| `AGENTIC_WIDGET_ASYNC_REPLIES` | auto | Empty + Pusher = job | `false` = wait on HTTP |
| `AGENTIC_WIDGET_STREAM` | `false` | Word-by-word `message.delta` (usually looks slow) | `true` = stream tokens; default waits for the full Pusher `message.created` |
| `AGENTIC_WIDGET_MESSAGE_BATCH_MS` | `0` | Debounce rapid user sends before one agent turn (async only) | e.g. `3000` = wait 3s after the last line, merge with newlines |
| `AGENTIC_WIDGET_MESSAGE_BATCH_MAX_MS` | `10000` | Cap wait during a long typing burst | Flush by this deadline even if the user keeps sending |
| `AGENTIC_WIDGET_ATTACHMENTS` | `true` | Image / PDF on the official widget | `false` = text only |
| `AGENTIC_WIDGET_HANDOFF` | `true` | In-chat handoff + staff inbox APIs | Custom desk: [STAFF_INBOX.md](./STAFF_INBOX.md) |
| `AGENTIC_WIDGET_BROADCAST_POLL_MS` | `3000` | Poll interval | Lower = more HTTP |
| `PUSHER_*` | empty | Pusher app | Needed for live widget |

`QUEUE_CONNECTION=database` (or redis) + `queue:work` for async replies.

### Widget replies (developer contract)

**Default is not token streaming.** Leave `AGENTIC_WIDGET_STREAM` unset or `false`. The model finishes, the job saves the assistant row, then Pusher (or polling) delivers **one** `message.created` with the full `payload.message` (`html` + `blocks`). The official widget shows **Typing** until that event, then renders the bubble. Do not paint `message.delta` fragments in a custom UI unless you explicitly set `AGENTIC_WIDGET_STREAM=true` (usually looks slow).

| Path | What the client does |
|------|----------------------|
| Async + Pusher (recommended) | `POST /messages` → `{ pending: true, conversation_id }` (and `batched: true` when message batching is on). Subscribe `{prefix}.{conversationId}`. Render on `message.created` / `message.resumed`. Typing starts when the batch flushes, not on each line. |
| Sync HTTP | `AGENTIC_WIDGET_ASYNC_REPLIES=false` — assistant HTML may be in the HTTP body. Still prefer realtime if Pusher is on. |
| Human handoff | After the visitor confirms in chat, `handoff` is true and **no** `ProcessWidgetMessageJob` runs. Staff replies use inbox APIs; they also broadcast `message.created`. Custom desk: [STAFF_INBOX.md](./STAFF_INBOX.md). |
| Images / PDF | `AGENTIC_WIDGET_ATTACHMENTS=true`. History and Pusher `message.html` include `<img>` with a **signed** file URL (`GET …/conversations/{id}/files/{file}`). Re-present HTML from the API; do not cache expired signed URLs. |

`GET /config?agent=` includes `stream: false` unless you opt in. Restart `queue:work` after changing stream / handoff / async env. Full events: [FRONTEND_IMPLEMENTATION_GUIDE.md](./FRONTEND_IMPLEMENTATION_GUIDE.md) §6. Embed flow: [WIDGET_EMBED_SDK.md](./WIDGET_EMBED_SDK.md).

### Admin, login, runtime API

| Key | Default | What it does |
|-----|---------|--------------|
| `AGENTIC_ADMIN_WEB_ENABLED` | `false` | Set `true` to open `/agentic/admin` |
| `AGENTIC_ADMIN_WEB_UI` | `spa` | `spa` or `blade` |
| `AGENTIC_ADMIN_PREFIX` | `agentic/admin` | Browser path |
| `AGENTIC_ADMIN_API_PREFIX` | `api/agentic/admin` | JSON path |
| `AGENTIC_ADMIN_LOCALE` | `en` | `en` / `ar` |
| `AGENTIC_ADMIN_REQUIRE_AUTH` | `true` | Sanctum on admin JSON. Set `false` only on a trusted laptop |
| `AGENTIC_ADMIN_WEB_REQUIRE_AUTH` | unset (auto) | Session `auth` on the admin page. Unset = required outside local/testing |
| `AGENTIC_ADMIN_GATE` | empty | Who is allowed. **Required on a live server** or admin/demo is 403 |
| `AGENTIC_API_ENABLED` | `false` | Extra `/api/agentic` CRUD (workflows, MCP, memory) |
| `AGENTIC_API_REQUIRE_AUTH` | `true` | Sanctum on that API, including workflow execute/resume |
| `AGENTIC_API_RATE_LIMIT_PER_MINUTE` | `120` | Raise if integrations hit 429 |
| `AGENTIC_WIDGET_RATE_LIMIT_ENABLED` | `true` | Cap widget JSON per IP + token prefix + guest id |
| `AGENTIC_WIDGET_RATE_LIMIT_PER_MINUTE` | `60` | Raise if a busy site hits 429; lower to slow abuse |

### Channels (widget + WhatsApp numbers)

Not the same as HTTP-tool OAuth (`agentic_connections`). Each row is one connection. WhatsApp: pick `meta_cloud` (live) or `webjs` (sidecar later). Attach as many numbers as you want.

| Key | Default | What it does |
|-----|---------|--------------|
| `AGENTIC_CHANNELS_ENABLED` | `true` | Public webhook routes under `/api/agentic/channels` |
| `AGENTIC_CHANNELS_PREFIX` | `api/agentic/channels` | Webhook path |
| `AGENTIC_CHANNELS_VERIFY_SIGNATURES` | `true` | Meta `X-Hub-Signature-256` and webjs `X-Agentic-Channel-Secret` |
| `AGENTIC_WHATSAPP_GRAPH_VERSION` | `v21.0` | Graph API version for Meta Cloud send |
| `AGENTIC_WHATSAPP_VERIFY_TOKEN` | empty | Shared Meta webhook verify (or set per account) |
| `AGENTIC_WHATSAPP_APP_SECRET` | empty | Shared Meta app secret (or set per account) |

How to connect numbers and the webjs sidecar shape: [CHANNELS.md](./CHANNELS.md). Scores: `POST /api/agentic/admin/evaluations` (`score` 1–5).

Admin + runtime APIs default **closed**. Laptop: set the two require-auth keys to `false`. Live: keep `true` and set `AGENTIC_ADMIN_GATE`.

### Memory and workflows

| Key | Default | What it does | Small / large | Tokens |
|-----|---------|--------------|---------------|--------|
| `AGENTIC_MEMORY_ENABLED` | `true` | Inject saved facts | `false` = ignore the table | — |
| `AGENTIC_MEMORY_MAX_CONTEXT_ENTRIES` | `20` | Rows pulled, then lean cap applies | Higher = more facts available | Then capped by context/widget memory limit |
| `AGENTIC_MEMORY_DEFAULT_TTL_DAYS` | empty | Optional expiry | — | — |
| `AGENTIC_WORKFLOWS_ENABLED` | `true` | Step graphs (not the widget) | — | Workflow agent steps use the same context keys |
| `AGENTIC_WORKFLOW_MAX_STEPS` | `100` | Safety cap | Higher = longer runs | Each agent step is another prompt |
| `AGENTIC_WORKFLOW_MAX_PARALLEL_BRANCHES` | `10` | Parallel fan-out | Higher = more load | — |
| `AGENTIC_WORKFLOW_RUN_RETENTION_DAYS` | `90` | How long to keep runs | Higher = more DB rows | — |

### HTTP tools and MCP

| Key | Default | What it does | Small / large |
|-----|---------|--------------|---------------|
| `AGENTIC_HTTP_ALLOW_PRIVATE_HOSTS` | `false` | Allow localhost/LAN from HTTP tools | Keep `false` on a live server |
| `AGENTIC_HTTP_MAX_RESPONSE_BYTES` | 5MB | Cap tool HTTP body | Higher = more RAM (not model tokens unless you put the body in context) |
| `AGENTIC_CODE_TOOLS_AUTO_REGISTER` | `true` | Discover `app/Agentic/Tools/Custom` | — |
| `AGENTIC_MCP_ENABLED` | `true` | MCP servers | — |
| `AGENTIC_MCP_MAX_RESOURCE_INJECTIONS` | `5` | MCP docs in the prompt | Higher = more tokens |

---

## Open views, login, and who is allowed

| Surface | URL | Lock |
|---------|-----|------|
| Admin page | `/agentic/admin` | Off until `AGENTIC_ADMIN_WEB_ENABLED=true`. Live: session login + gate |
| Admin JSON | `/api/agentic/admin` | Live: Sanctum + same gate |
| Demo chat | `/agentic/widget` | Live: same gate |
| Widget JSON | `/api/agentic/widget` | Embed token + allowed site origin |

```env
AGENTIC_ADMIN_WEB_ENABLED=true
AGENTIC_ADMIN_REQUIRE_AUTH=true
AGENTIC_API_REQUIRE_AUTH=true
AGENTIC_ADMIN_GATE=viewAgentic
```

```php
Gate::define('viewAgentic', fn ($user = null) => $user && $user->email === 'ops@yourcompany.com');
```

Widget guests: `X-Agentic-Guest-Id`. Production embed: `wgt_…` + `allowed_origins`.

---

## Widget on a site

```bash
php artisan vendor:publish --tag=agentic-widget-assets --force
php artisan agentic:widget-embed-token create --name=web --agents=support --origins=https://yoursite.com --guest
```

```blade
<x-agentic-widget agent="support" :token="env('AGENTIC_WIDGET_EMBED_TOKEN')" theme="isnaad" position="bottom-left" />
```

---

## Manage tools, skills, knowledge

Admin `/agentic/admin`: tool → skill → knowledge → published agent → embed token.

PHP tool: `php artisan agentic:make-code-tool CheckStock` then `agentic:code-tools-sync`. If default is `deny`, allow the names (`AGENTIC_PERMISSION_ALLOW_PATTERNS`).

---

## UI or CLI (pick one)

You can do **everything** from admin **or** from artisan. You do not need both.

Admin base: `/agentic/admin` · Admin API base: `/api/agentic/admin`

| Job | UI | CLI (asks questions, shows a summary, asks to save) | Admin API |
|-----|----|------------------------------------------------------|-----------|
| HTTP auth / OAuth refresh | `/connections` (headers, query, token extras) | `php artisan agentic:connection create --header=X-Store-Id:1 --query=locale=ar` | `POST /connections` · `POST /connections/{id}/refresh` |
| HTTP tool | `/tools/new` (pick the connection) | `php artisan agentic:make http-tool` | `POST /tools` |
| Clone tool | Tools list or tool page → Clone | `php artisan agentic:http-tool clone {slug}` | `POST /tools/{slug}/clone` |
| Test HTTP tool | Tool page → Test this HTTP tool | `php artisan agentic:http-tool test` | `POST /tools/{slug}/test` |
| PHP code tool | `/custom-code-tools` | `php artisan agentic:make code-tool` | `POST /code-handlers/publish` |
| Embed token | `/widget-embed-tokens` | `php artisan agentic:make embed-token` | `POST /widget-embed-tokens` |
| WhatsApp / widget account | `/channel-accounts` | `php artisan agentic:make channel-account` | `POST /channel-accounts` |
| Staff inbox (or your own desk) | `/inbox` | — | `GET /inbox` · `POST /conversations/{id}/take\|reply\|release` · [STAFF_INBOX.md](./STAFF_INBOX.md) |
| Agent | `/agents/new` | `php artisan agentic:make agent` | `POST /agents` |
| Skill | `/skills/new` | `php artisan agentic:make skill` | `POST /skills` |
| Knowledge + ingest | `/knowledge-sources` | `php artisan agentic:make knowledge` then `agentic:knowledge ingest` | `POST /knowledge-sources` · `POST …/ingest` |
| Evaluation score | `/evaluations` | `php artisan agentic:make evaluation` | `POST /evaluations` |
| List anything | same pages | `php artisan agentic:manage list` | `GET` the same resources |
| Force OAuth refresh | Refresh on the connection row | `php artisan agentic:connection refresh --slug=orders-oauth` | `POST /connections/{id}/refresh` |

`php artisan agentic:make` with no type opens a create menu.  
`php artisan agentic:manage` with no arguments asks **what** (create / list / delete) then **which resource**.

## Commands

| Command | When |
|---------|------|
| `php artisan agentic:install` | First time — interactive wizard (use `--quick` to skip) |
| `php artisan vendor:publish --tag=agentic-widget-assets --force` | After each package upgrade |
| `php artisan vendor:publish --tag=agentic-admin-assets --force` | After each package upgrade |
| `php artisan migrate` | Install / upgrade |
| `php artisan agentic:make-code-tool {Name}` | New PHP tool |
| `php artisan agentic:code-tools-sync` | After new tool classes |
| `php artisan agentic:mcp-sync` | After MCP config changes |
| `php artisan agentic:rag-validate {--offline}` | After embed/store changes |
| `php artisan agentic:widget-embed-token create\|list\|revoke` | New site / rotate |
| `php artisan agentic:make` | Interactive create (connection, HTTP tool, agent, …) |
| `php artisan agentic:manage` | Interactive create / list / delete for any resource |
| `php artisan agentic:connection` | HTTP auth / OAuth (create, list, refresh) |
| `php artisan agentic:http-tool` | HTTP tool create / list / test / clone |
| `php artisan agentic:channel-account` | WhatsApp / widget channel rows |
| `php artisan agentic:agent` | Agent create / list / delete |
| `php artisan agentic:skill` | Skill create / list / delete |
| `php artisan agentic:knowledge` | Knowledge create / list / ingest |
| `php artisan agentic:evaluation` | Score 1–5 create / list |
| `php artisan agentic:prune-workflow-runs` | Weekly cron |
| `php artisan queue:work` | Always in production |
| `php artisan config:clear` | After every `.env` edit |

---

## Memory vs chat vs workflow

- **Chat history** = this thread (capped by history keys).
- **Memory** = durable facts (name, language). Cap with memory + context limits.
- **Workflow** = a step graph in admin. Not the popup. Each agent step pays tokens again.
