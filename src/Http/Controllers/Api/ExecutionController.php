<?php

namespace Agentic\Http\Controllers\Api;

use Agentic\Contracts\Repositories\ExecutionRepository;
use Illuminate\Http\JsonResponse;

final class ExecutionController
{
    public function __construct(
        private ExecutionRepository $executions,
    ) {}

    public function show(string $id): JsonResponse
    {
        $execution = $this->executions->find($id);

        if ($execution === null) {
            return response()->json(['message' => 'Execution not found.'], 404);
        }

        return response()->json([
            'data' => [
                'id' => $execution->id,
                'agent' => $execution->agent,
                'status' => $execution->status->value,
                'conversation_id' => $execution->conversationId,
                'input' => $execution->input,
                'output' => $execution->output,
                'metadata' => $execution->metadata,
                'steps' => array_map(
                    fn ($step) => [
                        'name' => $step->name,
                        'status' => $step->status->value,
                        'input' => $step->input,
                        'output' => $step->output,
                    ],
                    $execution->steps,
                ),
            ],
        ]);
    }
}
