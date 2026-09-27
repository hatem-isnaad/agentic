<?php

namespace Agentic;

use Agentic\Agent\AgentResolver;
use Agentic\Context\ContextBuilder;
use Agentic\Context\ContextManager;
use Agentic\Contracts\Repositories\AgentRepository;
use Agentic\Contracts\Repositories\ConversationRepository;
use Agentic\Contracts\Repositories\ExecutionRepository;
use Agentic\Contracts\Repositories\KnowledgeRepository;
use Agentic\Contracts\Repositories\MemoryRepository;
use Agentic\Contracts\Repositories\SkillRepository;
use Agentic\Contracts\Repositories\ToolRepository;
use Agentic\Contracts\Repositories\WorkflowRepository;
use Agentic\Knowledge\Contracts\EmbeddingProvider;
use Agentic\Knowledge\Contracts\VectorStore;
use Agentic\Knowledge\Indexers\ArrayKnowledgeIndexer;
use Agentic\Knowledge\Indexers\VectorKnowledgeIndexer;
use Agentic\Knowledge\KnowledgeIngestor;
use Agentic\Knowledge\KnowledgeOrchestrator;
use Agentic\Console\SyncMcpToolsCommand;
use Agentic\Mcp\McpToolSyncService;
use Agentic\Knowledge\Providers\LaravelAiEmbeddingProvider;
use Agentic\Knowledge\Providers\NullEmbeddingProvider;
use Agentic\Knowledge\Retrievers\ArrayKnowledgeRetriever;
use Agentic\Knowledge\Retrievers\VectorKnowledgeRetriever;
use Agentic\Knowledge\Stores\ArrayVectorStore;
use Agentic\Knowledge\Stores\PgVectorStore;
use Agentic\Knowledge\Stores\PineconeVectorStore;
use Agentic\Conversation\ConversationManager;
use Agentic\Execution\ExecutionManager;
use Agentic\Integrations\LaravelAi\LaravelAiSdkAdapter;
use Agentic\Permission\DenyAllPermissionChecker;
use Agentic\Contracts\Connections\ConnectionResolver;
use Agentic\Connections\EloquentConnectionResolver;
use Agentic\Connections\OAuth2TokenManager;
use Agentic\Permission\PermissionChecker;
use Agentic\Permission\PermissionResolver;
use Agentic\Persistence\Eloquent\EloquentAgentRepository;
use Agentic\Persistence\Eloquent\EloquentConversationRepository;
use Agentic\Persistence\Eloquent\EloquentExecutionRepository;
use Agentic\Persistence\Eloquent\EloquentKnowledgeRepository;
use Agentic\Persistence\Eloquent\EloquentSkillRepository;
use Agentic\Persistence\Eloquent\EloquentToolRepository;
use Agentic\Persistence\InMemory\InMemoryConversationRepository;
use Agentic\Persistence\InMemory\InMemoryExecutionRepository;
use Agentic\Persistence\InMemory\InMemoryKnowledgeRepository;
use Agentic\Persistence\InMemory\InMemoryMemoryRepository;
use Agentic\Persistence\InMemory\InMemoryWorkflowRepository;
use Agentic\Persistence\Eloquent\EloquentWorkflowRepository;
use Agentic\Workflow\WorkflowResolver;
use Agentic\Workflow\WorkflowRunner;
use Agentic\Persistence\Eloquent\EloquentMemoryRepository;
use Agentic\Memory\MemoryContextResolver;
use Agentic\Memory\MemoryManager;
use Agentic\Persistence\ToolVersionPublisher;
use Agentic\Persistence\ToolVersionResolver;
use Agentic\Routing\AgentRouter;
use Agentic\Runtime\AgentRuntime;
use Agentic\Skill\SkillRegistry;
use Agentic\Skill\SkillResolver;
use Agentic\Skill\SkillRouter;
use Agentic\Skill\LlmSkillRouter;
use Agentic\Tool\Contracts\McpClientGateway;
use Agentic\Tool\DriverResolver;
use Agentic\Tool\Drivers\CodeToolDriver;
use Agentic\Tool\Drivers\HttpToolDriver;
use Agentic\Tool\Drivers\Http\AuthenticationResolver;
use Agentic\Tool\Drivers\Http\HttpRequestBuilder;
use Agentic\Tool\Drivers\Mcp\LaravelMcpClientGateway;
use Agentic\Tool\Drivers\Mcp\McpToolRegistrar;
use Agentic\Tool\Drivers\McpToolDriver;
use Agentic\Tool\Handlers\HandlerRegistry;
use Agentic\Tool\Registry\ToolRegistry;
use Agentic\Tool\ToolApprovalExecutionService;
use Agentic\Tool\ToolExecutor;
use Agentic\Tool\ToolFactory;
use Agentic\Widget\Broadcast\DatabaseWidgetBroadcastDriver;
use Agentic\Widget\Broadcast\WidgetBroadcastDriver;
use Illuminate\Support\Facades\Route;
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
        $this->app->singleton(LlmSkillRouter::class);
        $this->app->singleton(SkillRouter::class);
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
        $this->app->singleton(AuthenticationResolver::class, function ($app) {
            return new AuthenticationResolver(
                $app->make(ConnectionResolver::class),
                $app->make(OAuth2TokenManager::class),
            );
        });
        $this->app->singleton(HttpRequestBuilder::class, function ($app) {
            return new HttpRequestBuilder(
                $app->make(AuthenticationResolver::class),
            );
        });
        $this->app->singleton(HttpToolDriver::class, function ($app) {
            return new HttpToolDriver(
                $app->make(HttpRequestBuilder::class),
            );
        });
        $this->app->singleton(CodeToolDriver::class);
        $this->app->singleton(McpToolDriver::class);
        $this->app->singleton(McpToolRegistrar::class);
        $this->app->singleton(ExecutionManager::class);
        $this->app->singleton(OAuth2TokenManager::class);
        $this->app->singleton(WidgetBroadcastDriver::class, DatabaseWidgetBroadcastDriver::class);
        $this->app->singleton(ToolApprovalExecutionService::class);

        $this->app->singleton(PermissionChecker::class, function ($app) {
            return $app->make(config('agentic.permissions.checker', DenyAllPermissionChecker::class));
        });

        $this->app->bind(AgentRepository::class, EloquentAgentRepository::class);
        $this->app->bind(SkillRepository::class, EloquentSkillRepository::class);
        $this->app->bind(ToolRepository::class, EloquentToolRepository::class);
        $this->app->bind(ConnectionResolver::class, EloquentConnectionResolver::class);

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

        $this->app->bind(KnowledgeRepository::class, function ($app) {
            return config('agentic.knowledge.driver', 'eloquent') === 'memory'
                ? $app->make(InMemoryKnowledgeRepository::class)
                : $app->make(EloquentKnowledgeRepository::class);
        });

        $this->app->bind(MemoryRepository::class, function ($app) {
            return config('agentic.memory.driver', 'eloquent') === 'memory'
                ? $app->make(InMemoryMemoryRepository::class)
                : $app->make(EloquentMemoryRepository::class);
        });

        $this->app->singleton(MemoryContextResolver::class);
        $this->app->singleton(MemoryManager::class);

        $this->app->bind(WorkflowRepository::class, function ($app) {
            return config('agentic.workflows.driver', 'eloquent') === 'memory'
                ? $app->make(InMemoryWorkflowRepository::class)
                : $app->make(EloquentWorkflowRepository::class);
        });

        $this->app->singleton(WorkflowResolver::class);
        $this->app->singleton(WorkflowRunner::class);

        $this->app->singleton(EmbeddingProvider::class, function ($app) {
            $driver = config('agentic.knowledge.embedding', 'null');

            return $driver === 'laravel_ai'
                ? $app->make(LaravelAiEmbeddingProvider::class)
                : $app->make(NullEmbeddingProvider::class);
        });
        $this->app->singleton(ArrayVectorStore::class);
        $this->app->singleton(PgVectorStore::class);
        $this->app->singleton(PineconeVectorStore::class);
        $this->app->singleton(VectorStore::class, function ($app) {
            return match (config('agentic.knowledge.vector_store', 'array')) {
                'pgvector' => $app->make(PgVectorStore::class),
                'pinecone' => $app->make(PineconeVectorStore::class),
                default => $app->make(ArrayVectorStore::class),
            };
        });

        $this->app->singleton(KnowledgeOrchestrator::class, function ($app) {
            $orchestrator = new KnowledgeOrchestrator(
                $app->make(KnowledgeRepository::class),
                $app->make(SkillResolver::class),
            );

            $embeddings = $app->make(EmbeddingProvider::class);
            $store = $app->make(VectorStore::class);

            $orchestrator->extendRetriever(new ArrayKnowledgeRetriever());
            $orchestrator->extendRetriever(new VectorKnowledgeRetriever($embeddings, $store));
            $orchestrator->extendIndexer(new ArrayKnowledgeIndexer());
            $orchestrator->extendIndexer(new VectorKnowledgeIndexer($embeddings, $store));

            return $orchestrator;
        });

        $this->app->singleton(KnowledgeIngestor::class);
        $this->app->singleton(McpToolSyncService::class);

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
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'agentic');

        $this->publishes([
            __DIR__.'/../config/agentic.php' => config_path('agentic.php'),
        ], 'agentic-config');

        if (config('agentic.api.enabled')) {
            $prefix = trim((string) config('agentic.api.prefix', 'api/agentic'), '/');
            $middleware = $this->resolveMiddleware(
                config('agentic.api.middleware', ['api']),
                (bool) config('agentic.auth.protect.runtime_api', false),
            );

            Route::prefix($prefix)
                ->middleware($middleware)
                ->group(__DIR__.'/../routes/api.php');
        }

        if (config('agentic.admin.enabled') && config('agentic.admin.api.enabled', true)) {
            $prefix = trim((string) config('agentic.admin.api.prefix', 'api/agentic/admin'), '/');
            $middleware = $this->resolveMiddleware(
                config('agentic.admin.api.middleware', ['api']),
                (bool) config('agentic.auth.protect.admin_api', false),
            );
            $namePrefix = (string) config('agentic.admin.api.route_name_prefix', 'agentic.admin.');

            Route::prefix($prefix)
                ->middleware($middleware)
                ->name($namePrefix)
                ->group(__DIR__.'/../routes/admin-api.php');
        }

        if (config('agentic.widget.enabled', true)) {
            $prefix = trim((string) config('agentic.widget.prefix', 'api/agentic/widget'), '/');
            $middleware = config('agentic.widget.middleware', ['api']);
            $namePrefix = (string) config('agentic.widget.route_name_prefix', 'agentic.widget.');

            Route::prefix($prefix)
                ->middleware($middleware)
                ->name($namePrefix)
                ->group(__DIR__.'/../routes/widget-api.php');
        }

        if (config('agentic.auth.enabled', true) && class_exists(\Laravel\Sanctum\SanctumServiceProvider::class)) {
            $prefix = trim((string) config('agentic.auth.prefix', 'api/agentic/auth'), '/');
            $middleware = config('agentic.auth.middleware', ['api']);
            $namePrefix = (string) config('agentic.auth.route_name_prefix', 'agentic.auth.');

            Route::prefix($prefix)
                ->middleware($middleware)
                ->name($namePrefix)
                ->group(__DIR__.'/../routes/auth-api.php');
        }

        if ($this->app->runningInConsole()) {
            $this->commands([
                SyncMcpToolsCommand::class,
            ]);
        }
    }

    /**
     * @param  list<string|class-string>  $middleware
     * @return list<string|class-string>
     */
    private function resolveMiddleware(array $middleware, bool $requireAuth): array
    {
        if (! $requireAuth || ! class_exists(\Laravel\Sanctum\SanctumServiceProvider::class)) {
            return $middleware;
        }

        return array_merge($middleware, ['auth:sanctum']);
    }
}
