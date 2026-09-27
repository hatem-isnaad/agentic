<?php

namespace Agentic;

use Agentic\Agent\AgentResolver;
use Agentic\Context\ContextBuilder;
use Agentic\Context\ContextManager;
use Agentic\Contracts\Repositories\AgentRepository;
use Agentic\Contracts\Repositories\ConversationRepository;
use Agentic\Contracts\Repositories\ExecutionRepository;
use Agentic\Contracts\Repositories\SkillRepository;
use Agentic\Contracts\Repositories\ToolRepository;
use Agentic\Conversation\ConversationManager;
use Agentic\Execution\ExecutionManager;
use Agentic\Integrations\LaravelAi\LaravelAiSdkAdapter;
use Agentic\Permission\AllowAllPermissionChecker;
use Agentic\Permission\PermissionChecker;
use Agentic\Permission\PermissionResolver;
use Agentic\Persistence\Eloquent\EloquentAgentRepository;
use Agentic\Persistence\Eloquent\EloquentConversationRepository;
use Agentic\Persistence\Eloquent\EloquentExecutionRepository;
use Agentic\Persistence\Eloquent\EloquentSkillRepository;
use Agentic\Persistence\Eloquent\EloquentToolRepository;
use Agentic\Persistence\InMemory\InMemoryConversationRepository;
use Agentic\Persistence\InMemory\InMemoryExecutionRepository;
use Agentic\Persistence\ToolVersionPublisher;
use Agentic\Persistence\ToolVersionResolver;
use Agentic\Routing\AgentRouter;
use Agentic\Runtime\AgentRuntime;
use Agentic\Skill\SkillRegistry;
use Agentic\Skill\SkillResolver;
use Agentic\Tool\Contracts\McpClientGateway;
use Agentic\Tool\DriverResolver;
use Agentic\Tool\Drivers\CodeToolDriver;
use Agentic\Tool\Drivers\HttpToolDriver;
use Agentic\Tool\Drivers\Mcp\LaravelMcpClientGateway;
use Agentic\Tool\Drivers\Mcp\McpToolRegistrar;
use Agentic\Tool\Drivers\McpToolDriver;
use Agentic\Tool\Handlers\HandlerRegistry;
use Agentic\Tool\Registry\ToolRegistry;
use Agentic\Tool\ToolExecutor;
use Agentic\Tool\ToolFactory;
use Illuminate\Support\ServiceProvider;
use Laravel\Mcp\Client\ClientManager;
use Laravel\Mcp\Server\McpServiceProvider as LaravelMcpServiceProvider;

final class AgenticServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/agentic.php', 'agentic');

        if (class_exists(LaravelMcpServiceProvider::class)) {
            $this->app->register(LaravelMcpServiceProvider::class);
        }

        $this->app->singleton(ToolRegistry::class);
        $this->app->singleton(SkillRegistry::class);
        $this->app->singleton(SkillResolver::class);
        $this->app->singleton(HandlerRegistry::class);
        $this->app->singleton(DriverResolver::class);
        $this->app->singleton(ContextBuilder::class);
        $this->app->singleton(ContextManager::class);
        $this->app->singleton(ConversationManager::class);
        $this->app->singleton(PermissionResolver::class);
        $this->app->singleton(ToolExecutor::class);
        $this->app->singleton(LaravelAiSdkAdapter::class);
        $this->app->singleton(AgentRuntime::class);
        $this->app->singleton(AgentResolver::class);
        $this->app->singleton(AgentRouter::class, function ($app) {
            return new AgentRouter(config('agentic.routing.fallback_agent'));
        });
        $this->app->singleton(ToolFactory::class);
        $this->app->singleton(ToolVersionPublisher::class);
        $this->app->singleton(ToolVersionResolver::class);
        $this->app->singleton(HttpToolDriver::class);
        $this->app->singleton(CodeToolDriver::class);
        $this->app->singleton(McpToolDriver::class);
        $this->app->singleton(McpToolRegistrar::class);
        $this->app->singleton(ExecutionManager::class);

        $this->app->singleton(PermissionChecker::class, AllowAllPermissionChecker::class);

        $this->app->bind(AgentRepository::class, EloquentAgentRepository::class);
        $this->app->bind(SkillRepository::class, EloquentSkillRepository::class);
        $this->app->bind(ToolRepository::class, EloquentToolRepository::class);

        $this->app->bind(ExecutionRepository::class, function ($app) {
            return config('agentic.execution.driver', 'eloquent') === 'memory'
                ? $app->make(InMemoryExecutionRepository::class)
                : $app->make(EloquentExecutionRepository::class);
        });

        $this->app->bind(ConversationRepository::class, function ($app) {
            return config('agentic.conversation.driver', 'eloquent') === 'memory'
                ? $app->make(InMemoryConversationRepository::class)
                : $app->make(EloquentConversationRepository::class);
        });

        $this->app->singleton(McpClientGateway::class, function ($app) {
            if ($app->bound(ClientManager::class)) {
                return $app->make(LaravelMcpClientGateway::class);
            }

            return new \Agentic\Tool\Drivers\Mcp\ArrayMcpClientGateway();
        });
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->publishes([
            __DIR__.'/../config/agentic.php' => config_path('agentic.php'),
        ], 'agentic-config');
    }
}
