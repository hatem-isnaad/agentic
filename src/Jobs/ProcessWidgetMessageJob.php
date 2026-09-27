<?php

namespace Agentic\Jobs;

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
     */
    public function __construct(
        public string $agentSlug,
        public string $conversationId,
        public string $message,
        public array $metadata = [],
    ) {}

    public function handle(WidgetMessageService $messages): void
    {
        $messages->runAgentTurn(
            agentSlug: $this->agentSlug,
            conversationId: $this->conversationId,
            message: $this->message,
            metadata: $this->metadata,
        );
    }
}
