<?php

namespace Agentic;

use Agentic\Agent\AgentResolver;
use Agentic\Context\ContextBuilder;
use Agentic\Context\ContextManager;
use Agentic\Contracts\Repositories\AgentRepository;
use Agentic\Contracts\Repositories\ConversationRepository;
use Agentic\Contracts\Repositories\ExecutionRepository;
use Agentic\Contracts\Repositories\KnowledgeRepository;
use Agentic\Contracts\Repositories\SkillRepository;
use Agentic\Contracts\Repositories\ToolRepository;
use Agentic\Knowledge\Contracts\EmbeddingProvider;
use Agentic\Knowledge\Contracts\VectorStore;
use Agentic\Knowledge\Indexers\ArrayKnowledgeIndexer;
use Agentic\Knowledge\Indexers\VectorKnowledgeIndexer;
use Agentic\Knowledge\KnowledgeOrchestrator;
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
use Agentic\Permission\AllowAllPermissionChecker;
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
use Agentic\Persistence\ToolVersionPublisher;
use Agentic\Persistence\ToolVersionResolver;
use Agentic\Routing\AgentRouter;
use Agentic\Routing\RoutingConfigurator;
use Agentic\Routing\Support\AgentCatalogBuilder;
use Agentic\Routing\Support\AgentLlmSelector;
use Agentic\Runtime\AgentRuntime;
use Agentic\Skill\Routing\SkillRouter;
use Agentic\Skill\Routing\SkillRoutingConfigurator;
use Agentic\Skill\Routing\Strategies\DynamicKeywordSkillRoutingStrategy;
use Agentic\Skill\Routing\Strategies\LexicalSkillRoutingStrategy;
use Agentic\Skill\Routing\Support\LexicalSkillScorer;
use Agentic\Skill\Routing\Support\SkillKeywordMapBuilder;
use Agentic\Skill\Routing\Support\SkillLlmSelector;
use Agentic\Tool\ToolApprovalService;
use Agentic\Widget\Reply\HtmlReplyRenderer;
use Agentic\Widget\Reply\StructuredReplyBuilder;
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
        $this->app->singleton(HandlerRegistry::class);
        $this->app->singleton(DriverResolver::class);
        $this->app->singleton(ContextBuilder::class);
        $this->app->singleton(ContextManager::class);
        $this->app->singleton(ConversationManager::class);
        $this->app->singleton(PermissionResolver::class);
        $this->app->singleton(ToolExecutor::class);
        $this->app->singleton(ToolApprovalService::class);
        $this->app->singleton(HtmlReplyRenderer::class);
        $this->app->singleton(StructuredReplyBuilder::class);
        $this->app->singleton(LaravelAiSdkAdapter::class);
        $this->app->singleton(AgentRuntime::class);
        $this->app->singleton(AgentResolver::class);
        $this->app->singleton(AgentRouter::class, function ($app) {
            $router = new AgentRouter(config('agentic.routing.fallback_agent'));
            $app->make(RoutingConfigurator::class)->configure($router);

            return $router;
        });

        $this->app->singleton(SkillRouter::class, function ($app) {
            $router = new SkillRouter((string) config('agentic.skill_routing.fallback', 'all'));
            $app->make(SkillRoutingConfigurator::class)->configure($router);

            return $router;
        });

        $this->app->singleton(SkillLlmSelector::class);
        $this->app->singleton(SkillKeywordMapBuilder::class);
        $this->app->singleton(DynamicKeywordSkillRoutingStrategy::class);
        $this->app->singleton(LexicalSkillScorer::class);
        $this->app->singleton(LexicalSkillRoutingStrategy::class);
        $this->app->singleton(AgentLlmSelector::class);
        $this->app->singleton(AgentCatalogBuilder::class);
        $this->app->singleton(SkillRoutingConfigurator::class);
        $this->app->singleton(RoutingConfigurator::class);
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

        $this->app->singleton(InMemoryExecutionRepository::class);
        $this->app->singleton(InMemoryConversationRepository::class);
        $this->app->singleton(InMemoryKnowledgeRepository::class);

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

        if (config('agentic.api.enabled')) {
            $prefix = trim((string) config('agentic.api.prefix', 'api/agentic'), '/');
            $middleware = config('agentic.api.middleware', ['api']);

            Route::prefix($prefix)
                ->middleware($middleware)
                ->group(__DIR__.'/../routes/api.php');
        }

        if (config('agentic.admin.enabled') && config('agentic.admin.api.enabled', true)) {
            $prefix = trim((string) config('agentic.admin.api.prefix', 'api/agentic/admin'), '/');
            $middleware = config('agentic.admin.api.middleware', ['api']);
            $namePrefix = (string) config('agentic.admin.api.route_name_prefix', 'agentic.admin.api.');

            Route::prefix($prefix)
                ->middleware($middleware)
                ->name($namePrefix)
                ->group(__DIR__.'/../routes/admin-api.php');
        }

        if (class_exists(\Filament\Panel::class)) {
            $this->app->register(AgenticFilamentServiceProvider::class);
        }
    }
}
