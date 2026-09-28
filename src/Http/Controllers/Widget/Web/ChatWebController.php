<?php

namespace Agentic\Http\Controllers\Widget\Web;

use Agentic\Enums\Status;
use Agentic\Models\Agent;
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

        $embedToken = (string) (config('agentic.widget.embed.token') ?: config('services.agentic.widget_embed_token') ?: '');

        return view('agentic::widget.chat', [
            'agentSlug' => $agent?->slug ?? $agentSlug,
            'agentName' => $agent?->name ?? $agentSlug,
            'initialConversationId' => $conversationId,
            'embedToken' => $embedToken,
        ]);
    }
}
