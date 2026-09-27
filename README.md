# Agentic

**Agentic** is a Laravel-native, configuration-driven framework for building production AI agent systems on top of the [Laravel AI SDK](https://laravel.com/docs/ai-sdk). It provides orchestration, persistence, tools, skills, permissions, knowledge retrieval, execution tracking, and headless JSON APIs for separate **Admin** and **Widget** frontends.

| Layer | Responsibility |
|-------|----------------|
| **Your app** | Users, auth, business domain, React SPAs |
| **Agentic** | Agents, tools, skills, conversations, approvals, admin/widget APIs |
| **Laravel AI SDK** | Provider calls, streaming, native AI primitives |
| **LLM provider** | OpenAI, Anthropic, etc. |

```
Application (React admin + widget, domain services)
        ↓
Agentic (runtime, repositories, routing, knowledge, APIs)
        ↓
Laravel AI SDK
        ↓
LLM provider
```

**Design constraints:** The runtime never queries Eloquent directly; authorization is enforced before any tool driver runs; code tools never execute untrusted PHP from the UI.

---

## License

MIT. See [composer.json](composer.json).
