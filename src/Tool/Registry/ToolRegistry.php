<?php

namespace Agentic\Tool\Registry;

use Agentic\Tool\Contracts\ToolContract;
use InvalidArgumentException;

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

    public function has(string $name): bool
    {
        return isset($this->tools[$name]);
    }

    public function get(string $name): ToolContract
    {
        if (! $this->has($name)) {
            throw new InvalidArgumentException("Tool [{$name}] is not registered.");
        }

        return $this->tools[$name];
    }

    /** @return array<string, ToolContract> */
    public function all(): array
    {
        return $this->tools;
    }
}
