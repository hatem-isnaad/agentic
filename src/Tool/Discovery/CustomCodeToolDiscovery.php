<?php

namespace Agentic\Tool\Discovery;

use Agentic\Tool\Contracts\CodeToolHandler;
use Agentic\Tool\Contracts\DeclarativeCodeToolHandler;
use Agentic\Tool\Handlers\HandlerRegistry;
use Illuminate\Support\Str;
use ReflectionClass;

final class CustomCodeToolDiscovery
{
    /**
     * @return list<array{
     *     handler: string,
     *     class: string,
     *     description: string,
     *     input_schema: array<string, mixed>,
     *     registered: bool,
     *     source: string
     * }>
     */
    public function discover(): array
    {
        $items = [];
        $seen = [];

        foreach ($this->configuredClasses() as $class) {
            $this->appendClass($items, $seen, $class, 'config');
        }

        foreach ($this->scanPaths() as $class) {
            $this->appendClass($items, $seen, $class, 'path');
        }

        return $items;
    }

    public function registerDiscovered(HandlerRegistry $registry): int
    {
        $count = 0;

        foreach ($this->discover() as $item) {
            if ($registry->has($item['handler'])) {
                continue;
            }

            $registry->register($item['handler'], $item['class']);
            $count++;
        }

        return $count;
    }

    /**
     * @return list<class-string<CodeToolHandler>>
     */
    private function configuredClasses(): array
    {
        $classes = config('agentic.code_tools.classes', []);

        if (! is_array($classes)) {
            return [];
        }

        $resolved = [];

        foreach ($classes as $class) {
            if (is_string($class) && class_exists($class)) {
                $resolved[] = $class;
            }
        }

        return $resolved;
    }

    /**
     * @return list<class-string<CodeToolHandler>>
     */
    private function scanPaths(): array
    {
        $paths = config('agentic.code_tools.paths', []);
        $namespace = rtrim((string) config('agentic.code_tools.namespace', ''), '\\');

        if (! is_array($paths) || $namespace === '') {
            return [];
        }

        $classes = [];

        foreach ($paths as $path) {
            if (! is_string($path) || ! is_dir($path)) {
                continue;
            }

            foreach (glob($path.'/*.php') ?: [] as $file) {
                $base = basename($file, '.php');
                if ($base === '' || str_starts_with($base, '.')) {
                    continue;
                }

                $class = $namespace.'\\'.$base;

                if (class_exists($class)) {
                    $classes[] = $class;
                }
            }
        }

        return $classes;
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @param  array<string, true>  $seen
     * @param  class-string  $class
     */
    private function appendClass(array &$items, array &$seen, string $class, string $source): void
    {
        if (! is_subclass_of($class, CodeToolHandler::class)) {
            return;
        }

        if (isset($seen[$class])) {
            return;
        }

        $seen[$class] = true;

        $handler = $this->handlerNameForClass($class);
        $description = $class;
        $schema = ['type' => 'object', 'properties' => []];

        if (is_subclass_of($class, DeclarativeCodeToolHandler::class)) {
            $handler = $class::handlerName();
            $description = $class::toolDescription();
            $schema = $class::inputSchema();
        } else {
            $reflection = new ReflectionClass($class);
            $description = (string) $reflection->getShortName();
        }

        $registry = app(HandlerRegistry::class);

        $items[] = [
            'handler' => $handler,
            'class' => $class,
            'description' => $description,
            'input_schema' => $schema,
            'registered' => $registry->has($handler),
            'source' => $source,
        ];
    }

    /**
     * @param  class-string  $class
     */
    private function handlerNameForClass(string $class): string
    {
        if (is_subclass_of($class, DeclarativeCodeToolHandler::class)) {
            return $class::handlerName();
        }

        $short = class_basename($class);
        $short = Str::replaceLast('Tool', '', $short);
        $short = Str::replaceLast('Handler', '', $short);

        return Str::snake($short);
    }
}
