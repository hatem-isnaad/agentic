<?php

namespace Agentic\Http\Controllers\Admin;

use Agentic\Admin\Services\ToolAdminService;
use Agentic\Admin\DTO\ToolData;
use Agentic\Contracts\Repositories\ToolRepository;
use Agentic\Http\Support\AdminLocaleMeta;
use Agentic\Tool\Discovery\CustomCodeToolDiscovery;
use Agentic\Tool\Handlers\HandlerRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class CodeToolHandlerController
{
    public function __construct(
        private CustomCodeToolDiscovery $discovery,
        private HandlerRegistry $handlers,
        private ToolAdminService $tools,
        private ToolRepository $toolRepository,
    ) {}

    public function index(): JsonResponse
    {
        $handlers = $this->discovery->discover();
        $linked = $this->linkedToolsByHandler();

        $data = array_map(function (array $row) use ($linked) {
            $handler = $row['handler'];

            return array_merge($row, [
                'tool_slug' => $linked[$handler]['slug'] ?? null,
                'tool_status' => $linked[$handler]['status'] ?? null,
            ]);
        }, $handlers);

        return response()->json([
            'data' => $data,
            'meta' => array_merge(AdminLocaleMeta::build(), [
                'custom_path' => config('agentic.code_tools.paths.0'),
                'namespace' => config('agentic.code_tools.namespace'),
            ]),
        ]);
    }

    public function sync(): JsonResponse
    {
        $registered = $this->discovery->registerDiscovered($this->handlers);

        return response()->json([
            'data' => [
                'registered' => $registered,
                'handlers' => $this->discovery->discover(),
            ],
            'meta' => AdminLocaleMeta::build(),
        ]);
    }

    public function publish(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'handler' => ['required', 'string', 'max:191'],
            'slug' => ['nullable', 'string', 'max:191'],
            'name' => ['nullable', 'string', 'max:255'],
            'publish' => ['nullable', 'boolean'],
        ]);

        $handler = $validated['handler'];
        $meta = null;

        foreach ($this->discovery->discover() as $row) {
            if ($row['handler'] === $handler) {
                $meta = $row;
                break;
            }
        }

        if ($meta === null && ! $this->handlers->has($handler)) {
            return response()->json(['message' => "Handler [{$handler}] was not found."], 404);
        }

        if (! $this->handlers->has($handler)) {
            $this->handlers->register($handler, $meta['class']);
        }

        $name = $validated['name'] ?? (is_string($meta['description'] ?? null) ? $meta['description'] : $handler);
        $slug = $validated['slug'] ?? Str::slug(str_replace('.', '-', $handler));

        $tool = $this->tools->store(new ToolData(
            name: $name,
            slug: $slug,
            driver: 'code',
            description: is_string($meta['description'] ?? null) ? $meta['description'] : null,
            status: 'published',
            definition: [
                'handler' => $handler,
                'input_schema' => is_array($meta['input_schema'] ?? null) ? $meta['input_schema'] : [],
            ],
            publish: $validated['publish'] ?? true,
        ));

        return response()->json(['data' => $tool->toArray()], 201);
    }

    /**
     * @return array<string, array{slug: string, status: string|null}>
     */
    private function linkedToolsByHandler(): array
    {
        $map = [];

        foreach ($this->toolRepository->all() as $definition) {
            $handler = $definition->configuration['handler'] ?? null;

            if (! is_string($handler) || $handler === '') {
                continue;
            }

            $map[$handler] = [
                'slug' => $definition->name,
                'status' => $definition->status,
            ];
        }

        return $map;
    }
}
