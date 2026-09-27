<?php

namespace Agentic\Tool;

use Agentic\Contracts\Repositories\ToolRepository;
use Agentic\Exceptions\ToolNotFoundException;
use Agentic\Tool\Contracts\ToolContract;
use Agentic\Tool\Registry\ToolRegistry;

/**
 * Builds ConfiguredTool instances from definitions / persistence and registers them.
 */
final class ToolFactory
{
    public function __construct(
        private DriverResolver $drivers,
        private ToolRegistry $registry,
        private ToolRepository $tools,
    ) {}

    public function make(ToolDefinition $definition): ConfiguredTool
    {
        return new ConfiguredTool($definition, $this->drivers);
    }

    public function register(ToolDefinition $definition): ToolContract
    {
        $tool = $this->make($definition);
        $this->registry->register($tool);

        return $tool;
    }

    public function registerFromSlug(string $slug): ToolContract
    {
        $definition = $this->tools->findBySlug($slug);

        if ($definition === null) {
            throw new ToolNotFoundException($slug);
        }

        return $this->register($definition);
    }
}
