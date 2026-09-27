# Agentic

Agentic is a Laravel-native, extensible AI Agent orchestration framework built on the [Laravel AI SDK](https://laravel.com/docs/ai-sdk).

> Laravel AI SDK provides AI execution primitives.  
> Agentic provides dynamic Agent orchestration, configuration, persistence, tools, skills, permissions, context, execution, and extensibility.

## Architecture

```
Application
    ↓
Agentic (orchestration)
    ↓
Laravel AI SDK (AI execution)
    ↓
LLM Provider
```

Agentic owns Agent configuration, persistence, Tool registry/drivers, Skills, Permissions, Context, Execution lifecycle, Routing, and Knowledge orchestration.

The Runtime never queries Eloquent directly — repositories and resolvers sit between persistence and execution.

## Install

```bash
composer require hatem-isnaad/agentic
php artisan vendor:publish --tag=agentic-config
php artisan migrate
```

Requires PHP 8.3+ and `laravel/ai`.

## Quick start (code-defined)

```php
use Agentic\Agent\AgentDefinition;
use Agentic\Agent\ConfigurableAgent;
use Agentic\Execution\AgentExecutionContext;
use Agentic\Runtime\AgentRuntime;
use Agentic\Skill\SkillDefinition;
use Agentic\Skill\SkillRegistry;
use Agentic\Tool\Registry\ToolRegistry;

app(SkillRegistry::class)->register(new SkillDefinition(
    name: 'orders',
    description: 'Order operations',
    tools: ['orders.search'],
));

// Register ToolContract implementations on ToolRegistry...

$agent = new ConfigurableAgent(
    new AgentDefinition(
        name: 'Support',
        instructions: 'Help with orders.',
        skills: ['orders'],
        slug: 'support',
    ),
    app(AgentRuntime::class),
);

$result = $agent->run(new AgentExecutionContext('Where is my order?'));
```

## Persistence

Agents, Skills, Tools, and Tool versions are stored in `agentic_*` tables. Resolve them through repositories:

```php
use Agentic\Agent\AgentResolver;

$definition = app(AgentResolver::class)->resolve('support');
```

## HTTP tools

```php
use Agentic\Tool\ToolFactory;
use Agentic\Tool\ToolDefinition;
use Agentic\Tool\ToolExecutionContext;

app(ToolFactory::class)->register(new ToolDefinition(
    name: 'orders.get',
    description: 'Fetch an order by id',
    inputSchema: ['id' => ['type' => 'integer', 'required' => true]],
    driver: 'http',
    configuration: [
        'method' => 'GET',
        'url' => 'https://api.example.com/orders/{id}',
        'auth' => ['type' => 'bearer', 'token' => 'env:ORDERS_API_TOKEN'],
        'timeout' => 10,
        'retry' => ['times' => 2, 'sleep' => 100],
        'response_mapping' => [
            'order_id' => 'body.id',
            'status' => 'body.status',
        ],
    ],
));

$result = app(\Agentic\Tool\Registry\ToolRegistry::class)
    ->resolve('orders.get')
    ->execute(new ToolExecutionContext(arguments: ['id' => 42]));
```

## Code tools

Register trusted handlers in a service provider — never pass raw PHP from the UI.

```php
use Agentic\Tool\Handlers\HandlerRegistry;
use Agentic\Tool\Contracts\CodeToolHandler;
use Agentic\Tool\ToolExecutionContext;
use Agentic\Tool\ToolResult;

app(HandlerRegistry::class)->register('orders.search', new class implements CodeToolHandler {
    public function handle(ToolExecutionContext $context): ToolResult
    {
        return ToolResult::success(['hits' => []]);
    }
});

app(ToolFactory::class)->register(new ToolDefinition(
    name: 'orders.search',
    description: 'Search orders',
    driver: 'code',
    configuration: ['handler' => 'orders.search'],
));
```

## Extending tool drivers

```php
use Agentic\Tool\DriverResolver;

app(DriverResolver::class)->extend('custom', App\Agentic\CustomDriver::class);
```

Built-in driver names: `http`, `code`, `mcp` (MCP protocol delegated to Laravel AI SDK / `laravel/mcp`).

## Development

```bash
composer install
vendor/bin/phpunit
```

## Design rules

1. Runtime must not query Eloquent.
2. LLM output is never authorization — permissions run before Tool drivers.
3. Code tools must not evaluate arbitrary untrusted PHP.
4. Do not reimplement Laravel AI SDK capabilities (providers, streaming, MCP protocol, native tool contracts).
