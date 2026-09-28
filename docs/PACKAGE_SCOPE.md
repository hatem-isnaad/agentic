# Package scope — what “100%” means

Agentic is a **Laravel package**, not a standalone SaaS product.

## Included in the package (complete)

- Runtime orchestration on Laravel AI SDK (OpenAI, Anthropic, Gemini, Ollama, …)
- Persistence, APIs (runtime, admin, widget, auth hooks)
- Tools, skills, knowledge, memory, workflows, MCP sync + catalog
- RAG: Laravel AI embeddings, pgvector / Postgres native / Pinecone, `agentic:rag-validate`
- Security defaults, rate limits, production checklist
- Artisan: `agentic:install`, `agentic:rag-validate`, `agentic:prune-workflow-runs`, `agentic:mcp-sync`
- Optional Filament ops UI (CRUD + executions + workflow runs)
- PHPUnit coverage for core and API paths
- [HOST_BOOTSTRAP.md](./HOST_BOOTSTRAP.md) for host wiring

## Required in the host application

| Deliverable | Owner |
|-------------|--------|
| React (or other) **Admin SPA** | Host app |
| React (or other) **Widget SPA** | Host app |
| `laravel/sanctum`, passkeys, Pusher, etc. | Host `composer.json` |
| Production embeddings + vector DB credentials | Host `.env` + `php artisan agentic:rag-validate` |
| Custom `PermissionChecker` or tuned `RuleBasedPermissionChecker` | Host policy |
| End-to-end auth integration tests | Host test suite (package includes Sanctum smoke tests for auth + protected runtime/admin APIs) |
| Queues workers when `AGENTIC_KNOWLEDGE_QUEUE_REINDEX=true` | Host `queue:work` |

## Intentional limits

- **Parallel workflow branches** run concurrently via Laravel `Concurrency` (`AGENTIC_WORKFLOW_PARALLEL_DRIVER=process`). Hosts without pcntl fall back to isolated sequential branches.
- **WhatsApp webjs** stays a host sidecar (PHP does not run WhatsApp Web).
- **Filament** is an operator shortcut, not a replacement for the headless admin API or React dashboard.

When the host checklist in [PRODUCTION_CHECKLIST.md](./PRODUCTION_CHECKLIST.md) is done, the **stack** is production-ready—not only the package in isolation.
