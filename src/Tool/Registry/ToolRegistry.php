<?php

namespace Agentic\Tool\Registry;

use Agentic\Exceptions\ToolNotFoundException;
use Agentic\Tool\Contracts\ToolContract;
use InvalidArgumentException;

/**
 * Unified in-memory Tool discovery and resolution.
 *
 * Sources (DB, code, MCP, packages) hydrate this registry for the Runtime.
 */
final class ToolRegistry
{
    /** @var array<string, ToolContract> */
    private array $tools = [];

    public function register(ToolContract $tool): void
    {
        $name = $tool->definition()->name;

        if ($name === '') {
            throw new InvalidArgumentException('A tool name is required.');
        }

        $this->tools[$name] = $tool;
    }

    public function unregister(string $identifier): void
    {
        unset($this->tools[$identifier]);
    }

    public function has(string $identifier): bool
    {
        return isset($this->tools[$identifier]);
    }

    public function resolve(string $identifier): ToolContract
    {
        return $this->get($identifier);
    }

    public function find(string $identifier): ?ToolContract
    {
        return $this->tools[$identifier] ?? null;
    }

    public function get(string $identifier): ToolContract
    {
        if (! $this->has($identifier)) {
            throw new ToolNotFoundException($identifier);
        }

        return $this->tools[$identifier];
    }

    /** @return array<string, ToolContract> */
    public function all(): array
    {
        return $this->tools;
    }
}
