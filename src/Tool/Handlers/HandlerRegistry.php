<?php

namespace Agentic\Tool\Handlers;

use Agentic\Exceptions\ToolNotFoundException;
use Agentic\Tool\Contracts\CodeToolHandler;
use Closure;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

/**
 * Registry of trusted code-tool handlers.
 *
 * Only explicitly registered class names, container aliases, or closures are allowed.
 */
final class HandlerRegistry
{
    /** @var array<string, class-string<CodeToolHandler>|CodeToolHandler|Closure|string> */
    private array $handlers = [];

    public function __construct(
        private Container $container,
    ) {}

    /**
     * @param  class-string<CodeToolHandler>|CodeToolHandler|Closure|string  $handler
     */
    public function register(string $name, string|CodeToolHandler|Closure $handler): void
    {
        if ($name === '') {
            throw new InvalidArgumentException('A handler name is required.');
        }

        $this->handlers[$name] = $handler;
    }

    public function unregister(string $name): void
    {
        unset($this->handlers[$name]);
    }

    public function has(string $name): bool
    {
        return isset($this->handlers[$name]);
    }

    /**
     * @return CodeToolHandler|Closure
     */
    public function resolve(string $name): CodeToolHandler|Closure
    {
        if (! $this->has($name)) {
            throw new ToolNotFoundException("handler:{$name}");
        }

        $handler = $this->handlers[$name];

        if ($handler instanceof Closure || $handler instanceof CodeToolHandler) {
            return $handler;
        }

        $resolved = $this->container->make($handler);

        if ($resolved instanceof CodeToolHandler || $resolved instanceof Closure) {
            return $resolved;
        }

        if (is_callable($resolved)) {
            return Closure::fromCallable($resolved);
        }

        throw new InvalidArgumentException(
            "Handler [{$name}] must resolve to a CodeToolHandler or callable."
        );
    }

    /** @return array<string, class-string<CodeToolHandler>|CodeToolHandler|Closure|string> */
    public function all(): array
    {
        return $this->handlers;
    }
}
