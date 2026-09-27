<?php

namespace Agentic\Http\Controllers\Api;

use Agentic\Mcp\McpToolSyncService;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;

final class McpServerController
{
    public function __construct(
        private McpToolSyncService $sync,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json(['data' => $this->sync->servers()]);
    }

    public function sync(string $server): JsonResponse
    {
        try {
            $tools = $this->sync->sync($server);
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        }

        return response()->json([
            'data' => [
                'server' => $server,
                'tools' => $tools,
            ],
        ]);
    }
}
