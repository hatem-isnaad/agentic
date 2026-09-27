# Package scope — what “100%” means

Agentic is a **Laravel package**, not a standalone SaaS product.

## Included in the package (complete)

- Runtime orchestration on Laravel AI SDK
- Persistence, APIs (runtime, admin, widget, auth hooks)
- Tools, skills, knowledge, memory, workflows, MCP sync + catalog
- Security defaults, rate limits, production checklist
- Optional Filament ops UI (CRUD + executions viewer + agent↔skills)
- PHPUnit coverage for core and API paths

## Required in the host application

| Deliverable | Owner |
|-------------|--------|
| React (or other) **Admin SPA** | Host app |
| React (or other) **Widget SPA** | Host app |
| `laravel/sanctum`, passkeys, Pusher, etc. | Host `composer.json` |
| Production embeddings + vector DB credentials | Host `.env` |
| Custom `PermissionChecker` or tuned `RuleBasedPermissionChecker` | Host policy |
| End-to-end auth integration tests | Host test suite (package includes Sanctum smoke tests for auth + protected runtime/admin APIs) |
| Queues workers when `AGENTIC_KNOWLEDGE_QUEUE_REINDEX=true` | Host `queue:work` |

## Intentional limits

- **Parallel workflow branches** run sequentially in one PHP process (variables are isolated; results merge).
- **MCP prompts** are available via API; automatic prompt injection into agents is not enabled (use agent instructions or host logic).
- **Filament** is an operator shortcut, not a replacement for the headless admin API or React dashboard.

When the host checklist in [PRODUCTION_CHECKLIST.md](./PRODUCTION_CHECKLIST.md) is done, the **stack** is production-ready—not only the package in isolation.
