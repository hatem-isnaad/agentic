# Agentic

Agentic is a configuration-driven AI agent runtime and extensibility platform for Laravel.

## Architecture

- **Database/UI** — source of truth for agents, skills, tools, knowledge and permissions.
- **Runtime** — resolves configuration and executes agents.
- **Extensions** — PHP tools, no-code HTTP tools, installed package tools and future MCP tools.

The core intentionally stays small. Drivers and integrations are extension points, not hard-coded business logic.

## Development

```bash
composer install
vendor/bin/phpunit
```
