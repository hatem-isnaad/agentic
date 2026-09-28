<?php

namespace Agentic\Http\Controllers\Admin;

use Agentic\Admin\DTO\ConversationData;
use Agentic\Conversation\ConversationHandoffService;
use Agentic\Http\Responses\JsonApiResponse;
use Agentic\Http\Support\AdminPaginator;
use Agentic\Models\Conversation;
use Agentic\Models\ConversationMessage;
use Agentic\Widget\Broadcast\WidgetBroadcastDriver;
use Agentic\Widget\Reply\MarkdownHtmlConverter;
use Agentic\Widget\Support\WidgetReplyDelivery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class InboxController
{
    public function __construct(
        private ConversationHandoffService $handoff,
        private WidgetBroadcastDriver $broadcast,
        private MarkdownHtmlConverter $markdown,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $conversations = $this->handoff->inbox((int) $request->query('limit', 80));
        $models = Conversation::query()
            ->whereIn('uuid', array_map(fn ($conversation) => $conversation->id, $conversations))
            ->with('latestChatMessage')
            ->get()
            ->keyBy('uuid');

        $items = [];
        foreach ($conversations as $conversation) {
            $model = $models->get($conversation->id);
            $latest = $model?->latestChatMessage;
            $preview = trim(strip_tags((string) ($latest?->content_html ?? '')));
            $status = $this->handoff->status($conversation);
            $items[] = array_merge(ConversationData::fromConversation($conversation)->toArray(), [
                'preview' => mb_substr($preview, 0, 140),
                'last_role' => $latest?->role,
                'last_message_at' => optional($latest?->created_at ?? $model?->updated_at)?->toISOString() ?? $conversation->updatedAt,
                'handoff_status' => $status,
                'needs_human' => $this->handoff->isHuman($conversation),
            ]);
        }

        return response()->json(AdminPaginator::paginate($items, $request));
    }

    public function take(Request $request, string $id): JsonResponse
    {
        $by = $request->user()?->email;
        if (! is_string($by) || $by === '') {
            $by = trim((string) $request->input('by', 'staff')) ?: 'staff';
        }

        $conversation = $this->handoff->take($id, $by);
        $this->publishHandoffState($conversation->id);

        return JsonApiResponse::data(ConversationData::fromConversation($conversation)->toArray());
    }

    public function release(string $id): JsonResponse
    {
        $conversation = $this->handoff->release($id);
        $this->publishHandoffState($conversation->id);

        return JsonApiResponse::data(ConversationData::fromConversation($conversation)->toArray());
    }

    public function reply(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:8000'],
        ]);

        $conversation = $this->handoff->take($id, (string) ($request->user()?->email ?? 'staff'));
        $model = Conversation::query()->where('uuid', $conversation->id)->firstOrFail();

        $html = $this->markdown->convert(trim($validated['message']));

        $row = ConversationMessage::query()->create([
            'uuid' => (string) Str::uuid(),
            'conversation_id' => $model->id,
            'role' => 'assistant',
            'content_html' => $html,
            'format' => 'html',
            'locale' => app()->getLocale(),
            'metadata' => ['source' => 'staff'],
        ]);
        $model->touch();

        $payload = [
            'id' => $row->uuid,
            'cursor' => $row->id,
            'role' => 'assistant',
            'html' => $html,
            'format' => 'html',
            'created_at' => optional($row->created_at)?->toISOString(),
            'source' => 'staff',
        ];
        $this->broadcast->publish(
            WidgetReplyDelivery::conversationChannel($conversation->id),
            'message.created',
            ['message' => $payload],
        );

        return JsonApiResponse::created([
            'id' => $row->uuid,
            'role' => 'assistant',
            'html' => $row->content_html,
            'conversation_id' => $conversation->id,
        ]);
    }

    private function publishHandoffState(string $conversationId): void
    {
        $this->broadcast->publish(
            WidgetReplyDelivery::conversationChannel($conversationId),
            'handoff.updated',
            ['handoff' => $this->handoff->widgetPayloadForId($conversationId)],
        );
    }
}
