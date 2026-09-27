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

Built-in driver names: `http`, `code`, `mcp` (MCP protocol delegated to `laravel/mcp`).

## MCP tools

Register an MCP client (via `laravel/mcp`), then import tools into Agentic:

```php
use Laravel\Mcp\Facades\Mcp;
use Laravel\Mcp\Client;
use Agentic\Tool\Drivers\Mcp\McpToolRegistrar;

Mcp::registerClient('warehouse', fn () => Client::web('https://mcp.example.com'));

app(McpToolRegistrar::class)->registerServer('warehouse', 'mcp.warehouse.');
```

## Execution tracking

Every `AgentRuntime::run()` creates an execution with steps (`agent_start`, `llm_request`, `final_response`, …) and emits lifecycle events.

## Context providers

```php
use Agentic\Context\ContextManager;
use Agentic\Context\Providers\ArrayContextProvider;

app(ContextManager::class)->extend(new ArrayContextProvider([
    'tenant' => 'acme',
    'locale' => 'en',
]));
```

## Conversations

```php
use Agentic\Conversation\ConversationManager;
use Agentic\Execution\AgentExecutionContext;

$conversation = app(ConversationManager::class)->continueOrStart('support', userId: $user->id);

$runtime->run($agent, new AgentExecutionContext(
    message: 'Hello',
    conversation: $conversation,
));
```

## Routing

```php
use Agentic\Routing\AgentRouter;
use Agentic\Routing\RoutingContext;
use Agentic\Routing\Strategies\KeywordRoutingStrategy;
use Agentic\Routing\Strategies\SlugRoutingStrategy;

$router = app(AgentRouter::class)
    ->use(new SlugRoutingStrategy())
    ->use(new KeywordRoutingStrategy([
        'support' => ['refund', 'help'],
        'sales' => ['pricing', 'quote'],
    ]));

$result = $router->route(new RoutingContext(message: $request->input('message')));
// Then: AgentResolver::resolve($result->agent) → AgentRuntime::run(...)
```

## HTTP API

Enable JSON management and execution endpoints:

```env
AGENTIC_API_ENABLED=true
AGENTIC_API_PREFIX=api/agentic
```

Agents, skills, tools, and knowledge sources support list/show/create/update/delete. Executions and conversations are read-only. Agent execution and routing endpoints remain available as documented above.

## Vector stores

Configure knowledge vector retrieval:

```env
AGENTIC_VECTOR_STORE=array   # array | pgvector | pinecone
PINECONE_HOST=https://your-index.svc.pinecone.io
PINECONE_API_KEY=...
```

`pgvector` uses the `agentic_vector_entries` table with JSON vectors and cosine ranking (ideal for moderate corpora). `pinecone` delegates to the Pinecone HTTP API.

## Filament admin (optional)

Install Filament in your app, then register the Agentic plugin on your panel:

```php
use Agentic\Filament\AgenticPlugin;

public function panel(Panel $panel): Panel
{
    return $panel->plugin(AgenticPlugin::make());
}
```

Or set `AGENTIC_FILAMENT_PANELS=admin` so Agentic auto-registers on matching panel IDs when `filament/filament` is installed.

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
