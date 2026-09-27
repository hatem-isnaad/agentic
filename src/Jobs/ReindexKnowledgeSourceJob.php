<?php

namespace Agentic\Jobs;

use Agentic\Contracts\Repositories\KnowledgeRepository;
use Agentic\Knowledge\KnowledgeOrchestrator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class ReindexKnowledgeSourceJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $slug,
    ) {}

    public function handle(KnowledgeRepository $sources, KnowledgeOrchestrator $knowledge): void
    {
        $source = $sources->findBySlug($this->slug);

        if ($source === null) {
            return;
        }

        $knowledge->reindex($source);
    }
}
