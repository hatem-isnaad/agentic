<?php

namespace Agentic\Http\Controllers\Widget\Web;

use Agentic\Models\Agent;
use Agentic\Enums\Status;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class ChatWebController
{
    public function __invoke(Request $request): View
    {
        $agentSlug = (string) $request->query('agent', 'support');

        $agent = Agent::query()
            ->where('slug', $agentSlug)
            ->where('status', Status::Published)
            ->first();

        $conversationId = (string) $request->query('conversation', '');
        if ($conversationId === '') {
            $conversationId = null;
        }

        return view('agentic::widget.chat', [
            'agentSlug' => $agent?->slug ?? $agentSlug,
            'agentName' => $agent?->name ?? $agentSlug,
            'widgetApiPrefix' => '/'.trim((string) config('agentic.widget.prefix'), '/'),
            'initialConversationId' => $conversationId,
        ]);
    }
}
