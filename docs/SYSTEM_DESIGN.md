# Agentic — system design (frontend-facing)

**Last updated:** 2026-09-27

## Architecture

```mermaid
flowchart TB
  subgraph react [Host-owned React]
    A[Admin SPA]
    W[Widget SPA]
  end

  subgraph api [Agentic HTTP layer]
    AD["Admin API\n/api/agentic/admin"]
    WG["Widget API\n/api/agentic/widget"]
  end

  subgraph domain [Agentic domain services]
    AS[Admin Services + DTOs]
    WC[WidgetChatService]
    RT[AgentRuntime]
    TE[ToolExecutor + Approvals]
  end

  subgraph data [Persistence]
    ELO[Eloquent repositories]
    MSG[conversation_messages]
    APP[tool_approvals]
    WS[widget_settings]
    BE[broadcast_events]
  end

  subgraph external [External]
    AI[Laravel AI SDK]
    PU[Pusher / Socket.IO relay]
  end

  A --> AD --> AS --> ELO
  W --> WG --> WC --> RT --> AI
  WG --> TE
  WC --> MSG
  TE --> APP
  WG --> WS
  WC --> PU
  BE --> PU
```

## Chat message flow

```mermaid
sequenceDiagram
  participant U as User
  participant W as Widget SPA
  participant API as Widget API
  participant Chat as WidgetChatService
  participant RT as AgentRuntime
  participant LLM as Laravel AI SDK

  U->>W: Type message
  W->>API: POST /messages
  API->>Chat: send()
  Chat->>Chat: store user message
  Chat->>RT: run(agent, context)
  RT->>LLM: prompt + tools
  LLM-->>RT: text / tool calls
  RT-->>Chat: output
  Chat->>Chat: blocks + html + store assistant
  Chat->>API: broadcast message.created
  API-->>W: JSON response
  W->>U: Render blocks
```

## Tool approval + auto-resume

```mermaid
sequenceDiagram
  participant LLM as Laravel AI SDK
  participant TE as ToolExecutor
  participant W as Widget SPA
  participant API as Widget API
  participant EX as ToolApprovalExecutionService
  participant Chat as WidgetChatService

  LLM->>TE: tool orders.write
  TE-->>LLM: pending_approval JSON
  LLM-->>W: assistant mentions approval
  W->>API: POST /approvals/{id}/approve
  API->>EX: execute tool
  EX->>Chat: maybeResume()
  Chat->>Chat: system msg + new agent turn
  Chat-->>API: message.resumed broadcast
  API-->>W: execution.resume in response
  W->>W: append assistant message
```

## Configuration layers (widget)

Priority for per-agent widget behavior:

1. **Database** — `agentic_widget_settings.settings` (via Admin API)
2. **Config file** — `config/agentic.php` → `widget.agents.{slug}`
3. **Env / defaults** — `AGENTIC_WIDGET_*`

Public effective config is always returned by `GET /api/agentic/widget/config?agent=`.

## Realtime fan-out

| Backend driver | Storage / transport | Frontend subscription |
|----------------|---------------------|------------------------|
| `pusher` | Pusher HTTP API | `pusher-js` channel `prefix.conversationId` |
| `polling` | `agentic_broadcast_events` rows | HTTP long-poll `/realtime` |
| `socketio` | HTTP POST to host relay | Host Socket.IO client |
| `null` | none | HTTP only |

Event names are **identical** across drivers (see FRONTEND_IMPLEMENTATION_GUIDE §6).

## Security notes for UI builders

- Sanitize `html` blocks; prefer structured blocks when possible.
- Never expose Pusher secret or Socket.IO server tokens in the widget bundle — only public keys from `/config`.
- Implement host-app authentication before admin routes in production.
- Guest ids are identifiers, not secrets — still rate-limit widget endpoints at the gateway.

## Admin vs widget responsibility split

| Concern | Admin SPA | Widget SPA |
|---------|-----------|--------------|
| Agent CRUD | Yes | No |
| Widget theme/intake | Yes (DB settings) | Consume `/config` |
| End-user chat | Optional test execute | Yes |
| Tool approval UX | Optional monitoring | Yes (primary) |
| Translations | `GET /translations` | Config locale + host i18n |
