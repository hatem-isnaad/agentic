<?php

namespace Agentic\Http\Controllers\Admin;

use Agentic\Admin\Services\ConversationAdminService;
use Agentic\Http\Responses\JsonApiResponse;
use Agentic\Http\Support\AdminLocaleMeta;
use Agentic\Http\Support\AdminPaginator;
use Agentic\Widget\DTO\WidgetIdentity;
use Agentic\Widget\Services\WidgetConversationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ConversationController
{
    public function __construct(private ConversationAdminService $conversations, private WidgetConversationService $widgetConversations) {}

    public function index(Request $request): JsonResponse
    {
        $limit = min(500, max(1, (int) $request->query('fetch_limit', 200)));
        $agent = $request->query('agent');
        $agentSlug = is_string($agent) && $agent !== '' ? $agent : null;
        $items = array_map(fn ($row) => $row->toArray(), $this->conversations->list($limit, $agentSlug));

        return response()->json(AdminPaginator::paginate($items, $request));
    }

    public function messages(Request $request, string $id): JsonResponse
    {
        if ($this->conversations->find($id) === null) {
            return JsonApiResponse::error('Conversation not found.', 404);
        }

        $limit = min(100, max(1, (int) $request->query('limit', 50)));
        $page = $this->widgetConversations->messagesPage($id, new WidgetIdentity(null, null), $limit, adminFileUrls: true);

        return JsonApiResponse::data($page['messages'], meta: AdminLocaleMeta::build($page['meta']));
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
