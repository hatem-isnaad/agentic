<?php

namespace Agentic\Http\Controllers\Api;

use Agentic\Mcp\McpServerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

final class McpCatalogController
{
    public function __construct(
        private McpServerService $mcp,
    ) {}

    public function tools(string $server): JsonResponse
    {
        try {
            return response()->json(['data' => $this->mcp->tools($server)]);
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        }
    }

    public function resources(string $server): JsonResponse
    {
        try {
            return response()->json(['data' => $this->mcp->resources($server)]);
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        }
    }

    public function readResource(Request $request, string $server): JsonResponse
    {
        $validated = $request->validate([
            'uri' => ['required', 'string', 'max:2048'],
        ]);

        try {
            return response()->json([
                'data' => $this->mcp->readResource($server, $validated['uri']),
            ]);
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        }
    }

    public function prompts(string $server): JsonResponse
    {
        try {
            return response()->json(['data' => $this->mcp->prompts($server)]);
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        }
    }

    public function prompt(Request $request, string $server, string $name): JsonResponse
    {
        $validated = $request->validate([
            'arguments' => ['nullable', 'array'],
        ]);

        try {
            return response()->json([
                'data' => $this->mcp->prompt($server, $name, $validated['arguments'] ?? []),
            ]);
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        }
    }
}
