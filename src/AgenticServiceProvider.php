<?php

namespace Agentic;

use Agentic\Agent\AgentResolver;
use Agentic\Context\ContextBuilder;
use Agentic\Contracts\Repositories\AgentRepository;
use Agentic\Contracts\Repositories\SkillRepository;
use Agentic\Contracts\Repositories\ToolRepository;
use Agentic\Integrations\LaravelAi\LaravelAiSdkAdapter;
use Agentic\Permission\AllowAllPermissionChecker;
use Agentic\Permission\PermissionChecker;
use Agentic\Persistence\Eloquent\EloquentAgentRepository;
use Agentic\Persistence\Eloquent\EloquentSkillRepository;
use Agentic\Persistence\Eloquent\EloquentToolRepository;
use Agentic\Persistence\ToolVersionPublisher;
use Agentic\Persistence\ToolVersionResolver;
use Agentic\Runtime\AgentRuntime;
use Agentic\Skill\SkillRegistry;
use Agentic\Tool\DriverResolver;
use Agentic\Tool\Drivers\CodeToolDriver;
use Agentic\Tool\Drivers\HttpToolDriver;
use Agentic\Tool\Drivers\McpToolDriver;
use Agentic\Tool\Handlers\HandlerRegistry;
use Agentic\Tool\Registry\ToolRegistry;
use Agentic\Tool\ToolFactory;
use Illuminate\Support\ServiceProvider;

final class AgenticServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/agentic.php', 'agentic');

        $this->app->singleton(ToolRegistry::class);
        $this->app->singleton(SkillRegistry::class);
        $this->app->singleton(HandlerRegistry::class);
        $this->app->singleton(DriverResolver::class);
        $this->app->singleton(ContextBuilder::class);
        $this->app->singleton(LaravelAiSdkAdapter::class);
        $this->app->singleton(AgentRuntime::class);
        $this->app->singleton(AgentResolver::class);
        $this->app->singleton(ToolFactory::class);
        $this->app->singleton(ToolVersionPublisher::class);
        $this->app->singleton(ToolVersionResolver::class);
        $this->app->singleton(HttpToolDriver::class);
        $this->app->singleton(CodeToolDriver::class);
        $this->app->singleton(McpToolDriver::class);

        $this->app->singleton(PermissionChecker::class, AllowAllPermissionChecker::class);

        $this->app->bind(AgentRepository::class, EloquentAgentRepository::class);
        $this->app->bind(SkillRepository::class, EloquentSkillRepository::class);
        $this->app->bind(ToolRepository::class, EloquentToolRepository::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->publishes([
            __DIR__.'/../config/agentic.php' => config_path('agentic.php'),
        ], 'agentic-config');
    }
}
