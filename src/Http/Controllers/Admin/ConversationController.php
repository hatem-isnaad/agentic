<?php

namespace Agentic\Http\Controllers\Admin;

use Agentic\Admin\Services\ConversationAdminService;
use Illuminate\Contracts\View\View;

final class ConversationController
{
    public function __construct(
        private ConversationAdminService $conversations,
    ) {}

    public function index(): View
    {
        return view('agentic::admin.conversations.index', [
            'conversations' => $this->conversations->list(),
        ]);
    }

    public function show(string $id): View
    {
        $conversation = $this->conversations->find($id);

        abort_if($conversation === null, 404);

        return view('agentic::admin.conversations.show', [
            'conversation' => $conversation,
        ]);
    }
}
