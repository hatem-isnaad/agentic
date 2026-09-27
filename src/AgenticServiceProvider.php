<?php

namespace Agentic;

use Agentic\Permission\AllowAllPermissionChecker;
use Agentic\Permission\PermissionChecker;
use Agentic\Skill\SkillRegistry;
use Agentic\Tool\Registry\ToolRegistry;
use Illuminate\Support\ServiceProvider;

final class AgenticServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/agentic.php', 'agentic');
        $this->app->singleton(ToolRegistry::class);
        $this->app->singleton(SkillRegistry::class);
        $this->app->singleton(PermissionChecker::class, AllowAllPermissionChecker::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->publishes([
            __DIR__.'/../config/agentic.php' => config_path('agentic.php'),
        ], 'agentic-config');
    }
}
