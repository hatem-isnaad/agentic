# Agentic documentation

| Document | Audience |
|----------|----------|
| [HOST_BOOTSTRAP.md](./HOST_BOOTSTRAP.md) | **Start here in a Laravel host app** — install, env, validate, first agent |
| [DEVELOPER_QUICKSTART.md](./DEVELOPER_QUICKSTART.md) | Install → code tools → RAG → widget chatbot |
| [WIDGET_EMBED_SDK.md](./WIDGET_EMBED_SDK.md) | Embed popup chat on any site (`wgt_…`, themes, Pusher, HTML replies) |
| [CONFIGURE_BY_CODE.md](./CONFIGURE_BY_CODE.md) | UI → CLI → API → PHP map (persona, widget, tools) |
| [PRODUCTION_CHECKLIST.md](./PRODUCTION_CHECKLIST.md) | Launch hardening before go-live |
| [IMPLEMENTATION_STATUS.md](./IMPLEMENTATION_STATUS.md) | **What is actually in the repo** — routes, features, gaps |
| [FRONTEND_IMPLEMENTATION_GUIDE.md](./FRONTEND_IMPLEMENTATION_GUIDE.md) | **Complete API + UI spec** — routes, events, blocks, admin/widget screens |
| [COPY_PROMPT_FOR_AI.md](./COPY_PROMPT_FOR_AI.md) | **Copy-paste prompt** for Cursor/Claude to build React apps |
| [SYSTEM_DESIGN.md](./SYSTEM_DESIGN.md) | Architecture and sequence diagrams |
| [../.env.example](../.env.example) | Laravel backend environment variables |
| [../README.md](../README.md) | Package overview and PHP usage |

**Maintenance:** When you change `routes/admin-api.php`, `routes/widget-api.php`, or broadcast events, update the frontend guide, widget SDK, and copy prompt in the same PR.
