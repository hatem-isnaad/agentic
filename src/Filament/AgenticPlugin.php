<?php

namespace Agentic\Filament;

use Agentic\Filament\Resources\AgentResource;
use Agentic\Filament\Resources\ExecutionResource;
use Agentic\Filament\Resources\KnowledgeSourceResource;
use Agentic\Filament\Resources\SkillResource;
use Agentic\Filament\Resources\ToolResource;
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
            SkillResource::class,
            ToolResource::class,
            KnowledgeSourceResource::class,
            ExecutionResource::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        unset($panel);
    }
}
