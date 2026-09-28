<?php

namespace Agentic;

use Agentic\Agent\AgentResolver;
use Agentic\Channels\ChannelAgentRunner;
use Agentic\Connections\EloquentConnectionResolver;
use Agentic\Connections\OAuth2TokenManager;
use Agentic\Console\AgentCommand;
use Agentic\Console\ChannelAccountCommand;
use Agentic\Console\ConnectionCommand;
use Agentic\Console\EvalSetCommand;
use Agentic\Console\EvaluationCommand;
use Agentic\Console\HttpToolCommand;
use Agentic\Console\InstallAgenticCommand;
use Agentic\Console\KnowledgeCommand;
use Agentic\Console\MakeCodeToolCommand;
use Agentic\Console\MakeCommand;
use Agentic\Console\ManageCommand;
use Agentic\Console\PruneWorkflowRunsCommand;
use Agentic\Console\RagValidateCommand;
use Agentic\Console\SkillCommand;
use Agentic\Console\SyncCodeToolsCommand;
use Agentic\Console\SyncMcpToolsCommand;
use Agentic\Console\WidgetEmbedTokenCommand;
use Agentic\Context\ContextBuilder;
use Agentic\Context\ContextManager;
use Agentic\Context\LlmInputCompactor;
use Agentic\Context\LlmInstructionComposer;
use Agentic\Context\Providers\ConversationContextProvider;
use Agentic\Context\Providers\HttpRequestContextProvider;
use Agentic\Contracts\Connections\ConnectionResolver;
use Agentic\Contracts\Repositories\AgentRepository;
use Agentic\Contracts\Repositories\ConversationMessageRepository;
use Agentic\Contracts\Repositories\ConversationRepository;
use Agentic\Contracts\Repositories\ExecutionRepository;
use Agentic\Contracts\Repositories\KnowledgeRepository;
use Agentic\Contracts\Repositories\MemoryRepository;
use Agentic\Contracts\Repositories\SkillRepository;
use Agentic\Contracts\Repositories\ToolRepository;
use Agentic\Contracts\Repositories\WorkflowRepository;
use Agentic\Contracts\Repositories\WorkflowRunRepository;
use Agentic\Conversation\ConversationManager;
use Agentic\Execution\ExecutionManager;
use Agentic\Filament\AgenticPlugin;
use Agentic\Http\Middleware\AuthorizeAgenticAdmin;
use Agentic\Http\Support\AdminLocaleMeta;
use Agentic\Http\Support\AgenticExceptionRenderer;
use Agentic\Http\Support\AuthRequirement;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Agentic\Integrations\LaravelAi\LaravelAiSdkAdapter;
use Agentic\Knowledge\Contracts\EmbeddingProvider;
use Agentic\Knowledge\Contracts\VectorStore;
use Agentic\Knowledge\Documents\DocumentUrlFetcher;
use Agentic\Knowledge\Indexers\ArrayKnowledgeIndexer;
use Agentic\Knowledge\Indexers\VectorKnowledgeIndexer;
use Agentic\Knowledge\KnowledgeIngestor;
use Agentic\Knowledge\KnowledgeOrchestrator;
use Agentic\Knowledge\Providers\DeterministicEmbeddingProvider;
use Agentic\Knowledge\Providers\LaravelAiEmbeddingProvider;
use Agentic\Knowledge\Providers\NullEmbeddingProvider;
use Agentic\Knowledge\RagValidator;
use Agentic\Knowledge\Retrievers\ArrayKnowledgeRetriever;
use Agentic\Knowledge\Retrievers\VectorKnowledgeRetriever;
use Agentic\Knowledge\Stores\ArrayVectorStore;
use Agentic\Knowledge\Stores\PgVectorStore;
use Agentic\Knowledge\Stores\PineconeVectorStore;
use Agentic\Knowledge\Stores\PostgresPgvectorStore;
use Agentic\Mcp\McpAgentKnowledgeEnricher;
use Agentic\Mcp\McpServerService;
use Agentic\Mcp\McpToolSyncService;
use Agentic\Memory\MemoryContextResolver;
use Agentic\Memory\MemoryManager;
use Agentic\Permission\DenyAllPermissionChecker;
use Agentic\Permission\PermissionChecker;
use Agentic\Permission\PermissionResolver;
use Agentic\Support\AgenticDeployMode;
use Agentic\Support\WidgetOnlyMode;
use Agentic\Persistence\Eloquent\EloquentAgentRepository;
use Agentic\Persistence\Eloquent\EloquentConversationMessageRepository;
use Agentic\Persistence\Eloquent\EloquentConversationRepository;
use Agentic\Persistence\Eloquent\EloquentExecutionRepository;
use Agentic\Persistence\Eloquent\EloquentKnowledgeRepository;
use Agentic\Persistence\Eloquent\EloquentMemoryRepository;
use Agentic\Persistence\Eloquent\EloquentSkillRepository;
use Agentic\Persistence\Eloquent\EloquentToolRepository;
use Agentic\Persistence\Eloquent\EloquentWorkflowRepository;
use Agentic\Persistence\Eloquent\EloquentWorkflowRunRepository;
use Agentic\Persistence\InMemory\InMemoryConversationMessageRepository;
use Agentic\Persistence\InMemory\InMemoryConversationRepository;
use Agentic\Persistence\InMemory\InMemoryExecutionRepository;
use Agentic\Persistence\InMemory\InMemoryKnowledgeRepository;
use Agentic\Persistence\InMemory\InMemoryMemoryRepository;
use Agentic\Persistence\InMemory\InMemoryWorkflowRepository;
use Agentic\Persistence\InMemory\InMemoryWorkflowRunRepository;
use Agentic\Persistence\ToolVersionPublisher;
use Agentic\Persistence\ToolVersionResolver;
use Agentic\Routing\AgentRouter;
use Agentic\Runtime\AgentRuntime;
use Agentic\Skill\LlmSkillRouter;
use Agentic\Skill\SkillRegistry;
use Agentic\Skill\SkillResolver;
use Agentic\Skill\SkillRouter;
use Agentic\Support\AdminSpaAssets;
use Agentic\Support\WidgetEmbedAssets;
use Agentic\Tool\Contracts\McpClientGateway;
use Agentic\Tool\Discovery\CustomCodeToolDiscovery;
use Agentic\Tool\DriverResolver;
use Agentic\Tool\Drivers\CodeToolDriver;
use Agentic\Tool\Drivers\Http\AuthenticationResolver;
use Agentic\Tool\Drivers\Http\HttpRequestBuilder;
use Agentic\Tool\Drivers\HttpToolDriver;
use Agentic\Tool\Drivers\Mcp\ArrayMcpClientGateway;
use Agentic\Tool\Drivers\Mcp\LaravelMcpClientGateway;
use Agentic\Tool\Drivers\Mcp\McpToolRegistrar;
use Agentic\Tool\Drivers\McpToolDriver;
use Agentic\Tool\Handlers\HandlerRegistry;
use Agentic\Tool\Handlers\HandoffCodeHandler;
use Agentic\Tool\Registry\ToolRegistry;
use Agentic\Tool\ToolApprovalExecutionService;
use Agentic\Tool\ToolExecutor;
use Agentic\Tool\ToolFactory;
use Agentic\Widget\Broadcast\CompositeWidgetBroadcastDriver;
use Agentic\Widget\Broadcast\DatabaseWidgetBroadcastDriver;
use Agentic\Widget\Broadcast\PusherWidgetBroadcastDriver;
use Agentic\Widget\Broadcast\WidgetBroadcastDriver;
use Agentic\Workflow\WorkflowExecutionService;
use Agentic\Workflow\WorkflowResolver;
use Agentic\Workflow\WorkflowRunner;
use Filament\Facades\Filament;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Laravel\Mcp\Client\ClientManager;
use Laravel\Mcp\Server\McpServiceProvider as LaravelMcpServiceProvider;
use Laravel\Sanctum\SanctumServiceProvider;

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
        $this->app->singleton(CustomCodeToolDiscovery::class);
        $this->app->singleton(DriverResolver::class);
        $this->app->singleton(ContextBuilder::class);
        $this->app->singleton(ContextManager::class);
        $this->app->singleton(LlmInputCompactor::class, fn () => LlmInputCompactor::fromConfig());
        $this->app->singleton(LlmInstructionComposer::class);
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
        $this->app->singleton(WidgetBroadcastDriver::class, CompositeWidgetBroadcastDriver::class);
        $this->app->singleton(DatabaseWidgetBroadcastDriver::class);
        $this->app->singleton(PusherWidgetBroadcastDriver::class);
        $this->app->singleton(ToolApprovalExecutionService::class);

        $this->app->singleton(PermissionChecker::class, function ($app) {
            return $app->make(config('agentic.permissions.checker', DenyAllPermissionChecker::class));
        });

        $this->app->bind(AgentRepository::class, EloquentAgentRepository::class);
        $this->app->bind(ChannelAgentRunner::class, AgentRuntime::class);
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

        $this->app->bind(ConversationMessageRepository::class, function ($app) {
            return config('agentic.conversation.driver', 'eloquent') === 'memory'
                ? $app->make(InMemoryConversationMessageRepository::class)
                : $app->make(EloquentConversationMessageRepository::class);
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

        $this->app->bind(WorkflowRunRepository::class, function ($app) {
            return config('agentic.workflows.driver', 'eloquent') === 'memory'
                ? $app->make(InMemoryWorkflowRunRepository::class)
                : $app->make(EloquentWorkflowRunRepository::class);
        });

        $this->app->singleton(WorkflowExecutionService::class);
        $this->app->singleton(WorkflowResolver::class);
        $this->app->singleton(WorkflowRunner::class);

        $this->app->singleton(EmbeddingProvider::class, function ($app) {
            $driver = config('agentic.knowledge.embedding', 'null');

            if ($driver === 'laravel_ai') {
                return $app->make(LaravelAiEmbeddingProvider::class);
            }

            if ($driver === 'deterministic') {
                $store = (string) config('agentic.knowledge.vector_store', 'array');
                $dimensions = in_array($store, ['postgres', 'pinecone'], true)
                    ? (int) config('agentic.knowledge.pgvector.dimensions', 1536)
                    : 32;

                return new DeterministicEmbeddingProvider($dimensions);
            }

            return $app->make(NullEmbeddingProvider::class);
        });
        $this->app->singleton(ArrayVectorStore::class);
        $this->app->singleton(PgVectorStore::class);
        $this->app->singleton(PostgresPgvectorStore::class);
        $this->app->singleton(PineconeVectorStore::class);
        $this->app->singleton(VectorStore::class, function ($app) {
            return match (config('agentic.knowledge.vector_store', 'array')) {
                'pgvector' => $app->make(PgVectorStore::class),
                'postgres' => $app->make(PostgresPgvectorStore::class),
                'pinecone' => $app->make(PineconeVectorStore::class),
                default => $app->make(ArrayVectorStore::class),
            };
        });
        $this->app->singleton(RagValidator::class);

        $this->app->singleton(KnowledgeOrchestrator::class, function ($app) {
            $orchestrator = new KnowledgeOrchestrator(
                $app->make(KnowledgeRepository::class),
                $app->make(SkillResolver::class),
            );

            $embeddings = $app->make(EmbeddingProvider::class);
            $store = $app->make(VectorStore::class);

            $orchestrator->extendRetriever(new ArrayKnowledgeRetriever);
            $orchestrator->extendRetriever(new VectorKnowledgeRetriever($embeddings, $store));
            $orchestrator->extendIndexer(new ArrayKnowledgeIndexer);
            $orchestrator->extendIndexer(new VectorKnowledgeIndexer($embeddings, $store));

            return $orchestrator;
        });

        $this->app->singleton(DocumentUrlFetcher::class);
        $this->app->singleton(KnowledgeIngestor::class);
        $this->app->singleton(McpToolSyncService::class);
        $this->app->singleton(McpServerService::class);
        $this->app->singleton(McpAgentKnowledgeEnricher::class);

        $this->app->singleton(McpClientGateway::class, function ($app) {
            if ($app->bound(ClientManager::class)) {
                return $app->make(LaravelMcpClientGateway::class);
            }

            return new ArrayMcpClientGateway;
        });
    }

    public function boot(): void
    {
        AgenticDeployMode::applyPresets();

        $this->app->booted(function (): void {
            AgenticExceptionRenderer::register($this->app->make(ExceptionHandler::class));
        });

        $this->app->booted(function (): void {
            $this->app->make(HandlerRegistry::class)->register('handoff', HandoffCodeHandler::class);
        });

        if (config('agentic.code_tools.auto_register', true)) {
            $this->app->booted(function (): void {
                $this->app->make(CustomCodeToolDiscovery::class)
                    ->registerDiscovered($this->app->make(HandlerRegistry::class));
            });
        }

        $this->callAfterResolving(ContextManager::class, function (ContextManager $manager): void {
            $manager->extend($this->app->make(HttpRequestContextProvider::class));
            $manager->extend($this->app->make(ConversationContextProvider::class));
        });

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'agentic');
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'agentic');

        Blade::component('agentic::components.widget-embed', 'agentic-widget');
        // Host usage: <x-agentic-widget agent="support" :token="..." />

        View::composer('agentic::admin.*', function ($view): void {
            $meta = AdminLocaleMeta::build();
            $view->with([
                'adminLocale' => $meta['locale'],
                'adminSupportedLocales' => $meta['supported_locales'],
                'adminHtmlLang' => $meta['html_lang'],
                'adminDirection' => $meta['direction'],
            ]);
        });

        $this->publishes([
            __DIR__.'/../config/agentic.php' => config_path('agentic.php'),
        ], 'agentic-config');

        $this->publishes([
            AdminSpaAssets::distPath() => public_path('vendor/agentic/admin'),
        ], 'agentic-admin-assets');

        $this->publishes([
            WidgetEmbedAssets::distPath() => public_path('vendor/agentic/widget'),
        ], 'agentic-widget-assets');

        RateLimiter::for('agentic-api', function ($request) {
            return Limit::perMinute((int) config('agentic.api.rate_limit.per_minute', 120))
                ->by($request->user()?->getAuthIdentifier() ?: $request->ip());
        });

        RateLimiter::for('agentic-widget', function ($request) {
            $token = substr((string) $request->bearerToken(), 0, 16);
            $guest = (string) $request->header('X-Agentic-Guest-Id', '');

            return Limit::perMinute((int) config('agentic.widget.rate_limit.per_minute', 60))
                ->by($request->ip().'|'.$token.'|'.$guest);
        });

        RateLimiter::for('agentic-admin', function ($request) {
            return Limit::perMinute((int) config('agentic.admin.api.rate_limit.per_minute', 300))
                ->by($request->user()?->getAuthIdentifier() ?: $request->ip());
        });

        if (config('agentic.api.enabled')) {
            $prefix = trim((string) config('agentic.api.prefix', 'api/agentic'), '/');
            $apiMiddleware = config('agentic.api.middleware', ['api']);

            if ((bool) config('agentic.api.rate_limit.enabled', true)) {
                $apiMiddleware[] = 'throttle:agentic-api';
            }

            $middleware = $this->resolveMiddleware(
                $apiMiddleware,
                AuthRequirement::enabled(config('agentic.auth.protect.runtime_api')),
            );

            Route::prefix($prefix)
                ->middleware($middleware)
                ->name((string) config('agentic.api.route_name_prefix', 'agentic.api.'))
                ->group(__DIR__.'/../routes/api.php');
        }

        if (config('agentic.admin.enabled') && config('agentic.admin.api.enabled', true)) {
            $prefix = trim((string) config('agentic.admin.api.prefix', 'api/agentic/admin'), '/');
            $adminMiddleware = config('agentic.admin.api.middleware', ['api']);
            if ((bool) config('agentic.admin.api.rate_limit.enabled', true)) {
                $adminMiddleware = $this->appendMiddleware($adminMiddleware, 'throttle:agentic-admin');
            }
            $middleware = $this->resolveMiddleware(
                $adminMiddleware,
                AuthRequirement::enabled(config('agentic.auth.protect.admin_api')),
                authorizeAdmin: true,
            );
            $namePrefix = (string) config('agentic.admin.api.route_name_prefix', 'agentic.admin.');

            Route::prefix($prefix)
                ->middleware($middleware)
                ->name($namePrefix)
                ->group(__DIR__.'/../routes/admin-api.php');
        }

        if (config('agentic.admin.enabled') && config('agentic.admin.web.enabled', false)) {
            $prefix = trim((string) config('agentic.admin.web.prefix', 'agentic/admin'), '/');
            $middleware = config('agentic.admin.web.middleware', ['web']);
            if (AuthRequirement::enabled(config('agentic.auth.protect.admin_web'))) {
                $middleware = $this->appendMiddleware($middleware, 'auth');
            }
            $middleware = $this->appendMiddleware($middleware, AuthorizeAgenticAdmin::class);
            $namePrefix = (string) config('agentic.admin.web.route_name_prefix', 'agentic.admin.');

            $webRoutes = config('agentic.admin.web.ui', 'spa') === 'blade'
                ? __DIR__.'/../routes/admin-web.php'
                : __DIR__.'/../routes/admin-spa-web.php';

            Route::prefix($prefix)
                ->middleware($middleware)
                ->name($namePrefix)
                ->group($webRoutes);
        }

        if (config('agentic.widget.enabled', true)) {
            $prefix = trim((string) config('agentic.widget.prefix', 'api/agentic/widget'), '/');
            $middleware = config('agentic.widget.middleware', ['api']);
            if ((bool) config('agentic.widget.rate_limit.enabled', true)) {
                $middleware = $this->appendMiddleware($middleware, 'throttle:agentic-widget');
            }
            $namePrefix = (string) config('agentic.widget.route_name_prefix', 'agentic.widget.');

            Route::prefix($prefix)
                ->middleware($middleware)
                ->name($namePrefix)
                ->group(__DIR__.'/../routes/widget-api.php');
        }

        if (config('agentic.channels.enabled', true)) {
            $prefix = trim((string) config('agentic.channels.prefix', 'api/agentic/channels'), '/');
            $middleware = config('agentic.channels.middleware', ['api']);
            $namePrefix = (string) config('agentic.channels.route_name_prefix', 'agentic.channels.');

            Route::prefix($prefix)
                ->middleware($middleware)
                ->name($namePrefix)
                ->group(__DIR__.'/../routes/channels-api.php');
        }

        if (config('agentic.widget.enabled', true) && config('agentic.widget.web.enabled', true)) {
            $prefix = trim((string) config('agentic.widget.web.prefix', 'agentic/widget'), '/');
            $middleware = $this->appendMiddleware(
                config('agentic.widget.web.middleware', ['web']),
                AuthorizeAgenticAdmin::class,
            );
            $namePrefix = (string) config('agentic.widget.web.route_name_prefix', 'agentic.widget.web.');

            Route::prefix($prefix)
                ->middleware($middleware)
                ->name($namePrefix)
                ->group(__DIR__.'/../routes/widget-web.php');
        }

        if (config('agentic.auth.enabled', true) && class_exists(SanctumServiceProvider::class)) {
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
                InstallAgenticCommand::class,
                PruneWorkflowRunsCommand::class,
                RagValidateCommand::class,
                SyncMcpToolsCommand::class,
                MakeCodeToolCommand::class,
                SyncCodeToolsCommand::class,
                WidgetEmbedTokenCommand::class,
                ConnectionCommand::class,
                HttpToolCommand::class,
                ChannelAccountCommand::class,
                AgentCommand::class,
                SkillCommand::class,
                KnowledgeCommand::class,
                EvaluationCommand::class,
                EvalSetCommand::class,
                MakeCommand::class,
                ManageCommand::class,
            ]);
        }

        $this->registerFilamentPlugin();
    }

    private function registerFilamentPlugin(): void
    {
        if (AgenticDeployMode::isWidgetOnly()) {
            return;
        }

        if (! class_exists(Filament::class) || ! class_exists(AgenticPlugin::class)) {
            return;
        }

        $this->app->booted(function (): void {
            $panelIds = config('agentic.filament.panels', []);

            if ($panelIds === []) {
                return;
            }

            foreach ($panelIds as $panelId) {
                if (! is_string($panelId) || $panelId === '') {
                    continue;
                }

                try {
                    $panel = Filament::getPanel($panelId);
                    $panel->plugin(AgenticPlugin::make());
                } catch (\Throwable) {
                    continue;
                }
            }
        });
    }

    /**
     * @param  list<string|class-string>  $middleware
     * @return list<string|class-string>
     */
    private function resolveMiddleware(array $middleware, bool $requireAuth, bool $authorizeAdmin = false): array
    {
        if ($requireAuth && class_exists(SanctumServiceProvider::class)) {
            $middleware = $this->appendMiddleware($middleware, 'auth:sanctum');
        }

        if ($authorizeAdmin) {
            $middleware = $this->appendMiddleware($middleware, AuthorizeAgenticAdmin::class);
        }

        return $middleware;
    }

    /**
     * @param  list<string|class-string>  $middleware
     * @return list<string|class-string>
     */
    private function appendMiddleware(array $middleware, string $layer): array
    {
        if (in_array($layer, $middleware, true)) {
            return $middleware;
        }

        return array_merge($middleware, [$layer]);
    }
}
