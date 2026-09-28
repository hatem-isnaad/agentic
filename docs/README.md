# Agentic documentation

**New to the package?** Read [START_HERE.md](./START_HERE.md) first. It uses everyday words (agent, tool, widget) and tells you which file to open next.

| Document | When to open it |
|----------|-----------------|
| [START_HERE.md](./START_HERE.md) | **Anyone** — words, fresh vs finished project, first install |
| [HOST_BOOTSTRAP.md](./HOST_BOOTSTRAP.md) | Putting Agentic into a Laravel app — install, env, first agent |
| [DEVELOPER_HANDBOOK.md](./DEVELOPER_HANDBOOK.md) | **The only env catalog** — starter set, every key, small/large, tokens |
| [DEVELOPER_QUICKSTART.md](./DEVELOPER_QUICKSTART.md) | Short path: install → tools → RAG → chatbot |
| [WIDGET_EMBED_SDK.md](./WIDGET_EMBED_SDK.md) | Embed popup chat on any site (`wgt_…`, themes, Pusher, HTML replies) |
| [CHANNELS.md](./CHANNELS.md) | Widget + WhatsApp connections (Meta Cloud now, webjs sidecar later) |
| [CONFIGURE_BY_CODE.md](./CONFIGURE_BY_CODE.md) | UI → CLI → API → PHP map (persona, widget, tools) |
| [PRODUCTION_CHECKLIST.md](./PRODUCTION_CHECKLIST.md) | Launch hardening before go-live |
| [IMPLEMENTATION_STATUS.md](./IMPLEMENTATION_STATUS.md) | **What is actually in the repo** — routes, features, gaps |
| [FRONTEND_IMPLEMENTATION_GUIDE.md](./FRONTEND_IMPLEMENTATION_GUIDE.md) | **Complete API + UI spec** — routes, events, blocks, admin/widget screens |
| [COPY_PROMPT_FOR_AI.md](./COPY_PROMPT_FOR_AI.md) | **Copy-paste prompt** for Cursor/Claude to build React apps |
| [SYSTEM_DESIGN.md](./SYSTEM_DESIGN.md) | Architecture and sequence diagrams |
| [../.env.example](../.env.example) | Laravel backend environment variables |
| [../README.md](../README.md) | Package overview and PHP usage |

**Maintenance:** When you change `routes/admin-api.php`, `routes/widget-api.php`, or broadcast events, update the frontend guide, widget SDK, and copy prompt in the same PR.
