<?php

namespace Agentic\Tool;

use Agentic\Exceptions\DriverNotFoundException;
use Agentic\Tool\Contracts\ToolDriver;
use Illuminate\Contracts\Container\Container;

/**
 * Resolves ToolDriver instances by name without coupling Runtime to driver classes.
 */
final class DriverResolver
{
    /** @var array<string, class-string<ToolDriver>|ToolDriver> */
    private array $extensions = [];

    public function __construct(
        private Container $container,
    ) {}

    /**
     * @param  class-string<ToolDriver>|ToolDriver  $driver
     */
    public function extend(string $name, string|ToolDriver $driver): void
    {
        $this->extensions[$name] = $driver;
    }

    public function resolve(string $name): ToolDriver
    {
        $driver = $this->extensions[$name] ?? config("agentic.tool_drivers.{$name}");

        if ($driver === null) {
            throw new DriverNotFoundException($name);
        }

        if ($driver instanceof ToolDriver) {
            return $driver;
        }

        $instance = $this->container->make($driver);

        if (! $instance instanceof ToolDriver) {
            throw new DriverNotFoundException($name);
        }

        return $instance;
    }

    public function has(string $name): bool
    {
        return isset($this->extensions[$name]) || config("agentic.tool_drivers.{$name}") !== null;
    }
}
