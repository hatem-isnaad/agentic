<?php

namespace Agentic\Jobs;

use Agentic\Conversation\ConversationHandoffService;
use Agentic\Widget\Services\WidgetMessageBatchCoordinator;
use Agentic\Widget\Services\WidgetMessageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class ProcessWidgetBatchedAgentTurnJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $agentSlug,
        public string $conversationId,
        public array $metadata,
        public int $flushAtUnix,
        public string $scheduleToken,
    ) {}

    public function handle(
        WidgetMessageBatchCoordinator $batch,
        WidgetMessageService $messages,
        ConversationHandoffService $handoff,
    ): void {
        if (! $batch->shouldFlush($this->conversationId, $this->flushAtUnix, $this->scheduleToken)) {
            if (now()->timestamp < $this->flushAtUnix) {
                $this->release($this->flushAtUnix - now()->timestamp);
            }

            return;
        }

        if ($handoff->isHumanId($this->conversationId)) {
            $batch->forget($this->conversationId);
            $messages->stopQueuedAgentTurn($this->conversationId);

            return;
        }

        $composed = $batch->composePendingUserTurn($this->conversationId);
        $batch->forget($this->conversationId);

        if ($composed === null || (trim($composed['message']) === '' && $composed['attachments'] === [])) {
            return;
        }

        $messages->runAgentTurn(
            $this->agentSlug,
            $this->conversationId,
            $composed['message'],
            $this->metadata,
            attachments: $composed['attachments'],
        );
    }
}
