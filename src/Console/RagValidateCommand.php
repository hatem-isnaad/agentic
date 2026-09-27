<?php

namespace Agentic\Console;

use Agentic\Knowledge\RagValidator;
use Illuminate\Console\Command;

final class RagValidateCommand extends Command
{
    protected $signature = 'agentic:rag-validate {--offline : Use deterministic embeddings (no AI API calls)}';

    protected $description = 'Index a probe document and verify vector retrieval against the configured store';

    public function handle(RagValidator $validator): int
    {
        $offline = (bool) $this->option('offline');

        if (! $offline && config('agentic.knowledge.embedding', 'null') === 'null') {
            $this->error('Set AGENTIC_KNOWLEDGE_EMBEDDING=laravel_ai or pass --offline.');

            return self::FAILURE;
        }

        if ($offline) {
            $this->warn('Offline mode: deterministic embeddings (no AI API calls).');
        }

        $store = (string) config('agentic.knowledge.vector_store', 'array');
        $this->line("Vector store: {$store}");
        $this->line('Embedding driver: '.($offline ? 'deterministic' : (string) config('agentic.knowledge.embedding', 'null')));

        $result = $validator->validate($offline ? $validator->offlineEmbeddings() : null);

        if ($result['top_score'] !== null) {
            $this->line('Top score: '.round($result['top_score'], 4));
        }

        if (is_string($result['top_content'])) {
            $this->line('Top chunk: '.mb_substr($result['top_content'], 0, 120).'...');
        }

        if ($result['success']) {
            $this->info($result['message']);

            return self::SUCCESS;
        }

        $this->error($result['message']);

        return self::FAILURE;
    }
}
