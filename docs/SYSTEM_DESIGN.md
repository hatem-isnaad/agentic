# Agentic system design

High-level architecture for the Laravel package. For route-level detail see [IMPLEMENTATION_STATUS.md](./IMPLEMENTATION_STATUS.md) and [FRONTEND_IMPLEMENTATION_GUIDE.md](./FRONTEND_IMPLEMENTATION_GUIDE.md).

```mermaid
flowchart TB
  subgraph clients [Host application]
    AdminSPA[Admin React SPA]
    WidgetSPA[Widget React SPA]
    Domain[Domain services]
  end

  subgraph agentic [Agentic package]
    AdminAPI[admin-api.php]
    WidgetAPI[widget-api.php]
    RuntimeAPI[api.php optional]
    AuthAPI[auth-api.php optional]
    Resolver[Agent / Skill / Tool resolvers]
    Workflow[WorkflowRunner]
    Knowledge[KnowledgeOrchestrator + Ingestor]
    Memory[MemoryManager]
    Runtime[AgentRuntime]
  end

  subgraph sdk [Laravel AI SDK]
    AIAgent[Agent + Tools]
    Providers[LLM providers]
  end

  AdminSPA --> AdminAPI
  WidgetSPA --> WidgetAPI
  Domain --> RuntimeAPI
  AdminAPI --> Resolver
  WidgetAPI --> Runtime
  RuntimeAPI --> Resolver
  Resolver --> Runtime
  Workflow --> Runtime
  Runtime --> AIAgent --> Providers
  Knowledge --> Runtime
  Memory --> Runtime
```

## Design rules

1. **Agentic orchestrates; Laravel AI SDK executes** model calls and native tool/deferred-loading primitives.
2. **Permissions run before ToolDriver** — denied tools never hit HTTP/Code/MCP drivers.
3. **Published tool versions are immutable** — executions pin `tool_version_id`.
4. **Runtime does not query Eloquent directly** — repositories + resolvers only.
5. **Headless JSON** — production UI lives in the host app (React admin + widget).

## Widget reply path (no token streaming)

Default: `AGENTIC_WIDGET_STREAM=false`. The SDK uses `prompt()`, not `stream()`. The client renders **once** on `message.created`.

```mermaid
sequenceDiagram
  participant W as Widget / custom UI
  participant API as Widget API
  participant Q as ProcessWidgetMessageJob
  participant P as Pusher / poll
  W->>API: POST /messages
  API-->>W: pending + conversation_id
  API->>P: assistant.typing active
  API->>Q: dispatch (skipped if human handoff)
  Q->>Q: AgentRuntime prompt()
  Q->>P: message.created (full html)
  Q->>P: assistant.typing off
  P-->>W: render one bubble
```

Opt-in `AGENTIC_WIDGET_STREAM=true` adds `message.delta` during the job. Official embed ignores deltas. After handoff, the job no-ops. Staff replies still publish `message.created`. See [DEVELOPER_HANDBOOK.md](./DEVELOPER_HANDBOOK.md) § Widget replies and [FRONTEND_IMPLEMENTATION_GUIDE.md](./FRONTEND_IMPLEMENTATION_GUIDE.md) §6.
