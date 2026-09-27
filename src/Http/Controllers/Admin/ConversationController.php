<?php

namespace Agentic\Http\Controllers\Admin;

use Agentic\Admin\Services\ConversationAdminService;
use Agentic\Http\Support\AdminLocaleMeta;
use Agentic\Http\Support\AdminPaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ConversationController
{
    public function __construct(
        private ConversationAdminService $conversations,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $limit = min(500, max(1, (int) $request->query('fetch_limit', 200)));
        $items = array_map(fn ($row) => $row->toArray(), $this->conversations->list($limit));

        return response()->json(AdminPaginator::paginate($items, $request));
    }

    public function show(string $id): JsonResponse
    {
        $conversation = $this->conversations->find($id);

        if ($conversation === null) {
            return response()->json(['message' => 'Conversation not found.'], 404);
        }

        return response()->json(['data' => $conversation->toArray(), 'meta' => AdminLocaleMeta::build()]);
    }
}
