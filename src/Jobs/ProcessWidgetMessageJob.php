<?php

namespace Agentic\Jobs;

use Agentic\Conversation\ConversationHandoffService;
use Agentic\Widget\Services\WidgetMessageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class ProcessWidgetMessageJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @param  array<string, mixed>  $metadata
     * @param  list<array<string, mixed>>  $attachments
     */
    public function __construct(
        public string $agentSlug,
        public string $conversationId,
        public string $message,
        public array $metadata = [],
        public array $attachments = [],
    ) {}

    public function handle(WidgetMessageService $messages, ConversationHandoffService $handoff): void
    {
        if ($handoff->isHumanId($this->conversationId)) {
            $messages->stopQueuedAgentTurn($this->conversationId);

            return;
        }

        $messages->runAgentTurn($this->agentSlug, $this->conversationId, $this->message, $this->metadata, attachments: $this->attachments);
    }
}
