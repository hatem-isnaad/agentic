<?php

namespace Agentic\Filament;

use Agentic\Filament\Resources\AgentResource;
use Agentic\Filament\Resources\KnowledgeSourceResource;
use Agentic\Filament\Resources\SkillResource;
use Agentic\Filament\Resources\ToolResource;
use Agentic\Filament\Resources\WorkflowResource;
use Filament\Contracts\Plugin;
use Filament\Panel;

final class AgenticPlugin implements Plugin
{
    public static function make(): self
    {
        return app(self::class);
    }

    public function getId(): string
    {
        return 'agentic';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            AgentResource::class,
            KnowledgeSourceResource::class,
            SkillResource::class,
            ToolResource::class,
            WorkflowResource::class,
        ]);
    }

    public function boot(Panel $panel): void {}
}
