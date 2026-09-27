<?php

namespace Agentic\Http\Controllers\Admin\Web;

use Agentic\Models\Conversation;
use Agentic\Models\ConversationMessage;
use Illuminate\Contracts\View\View;

final class ConversationWebController
{
    public function index(): View
    {
        return view('agentic::admin.conversations.index', [
            'conversations' => Conversation::query()->orderByDesc('updated_at')->limit(100)->get(),
        ]);
    }

    public function show(string $id): View
    {
        $conversation = Conversation::query()->findOrFail($id);

        return view('agentic::admin.conversations.show', [
            'conversation' => $conversation,
            'messages' => ConversationMessage::query()
                ->where('conversation_id', $conversation->id)
                ->orderBy('id')
                ->get(),
        ]);
    }
}
