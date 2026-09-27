<?php

namespace Agentic;

use Illuminate\Support\ServiceProvider;
use Agentic\Tool\Registry\ToolRegistry;

final class AgenticServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/agentic.php', 'agentic');

        $this->app->singleton(ToolRegistry::class);
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/agentic.php' => config_path('agentic.php'),
        ], 'agentic-config');
    }
}
