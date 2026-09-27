<?php

namespace Agentic\Knowledge;

use Agentic\Contracts\Repositories\KnowledgeRepository;
use Agentic\Jobs\ReindexKnowledgeSourceJob;
use Agentic\Knowledge\Documents\DocumentCollector;
use Agentic\Knowledge\Documents\DocumentParserResolver;
use Agentic\Knowledge\Documents\DocumentUrlFetcher;

final class KnowledgeIngestor
{
    public function __construct(
        private KnowledgeRepository $sources,
        private KnowledgeOrchestrator $orchestrator,
        private DocumentParserResolver $parsers = new DocumentParserResolver(),
        private DocumentCollector $collector = new DocumentCollector(),
        private DocumentUrlFetcher $urlFetcher,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function ingest(string $slug, array $payload, bool $reindex = true): KnowledgeSourceDefinition
    {
        $source = $this->sources->findBySlug($slug);

        if ($source === null) {
            throw new \InvalidArgumentException("Knowledge source [{$slug}] was not found.");
        }

        $format = strtolower((string) ($payload['format'] ?? 'text'));
        $documentPayload = $payload['documents'] ?? ($payload['raw_text'] ?? $payload['content'] ?? []);

        if (isset($payload['urls']) && is_array($payload['urls']) && $payload['urls'] !== []) {
            $fetched = $this->urlFetcher->fetchMany($payload['urls']);
            $documentPayload = is_array($documentPayload)
                ? array_merge($fetched, $documentPayload)
                : $fetched;
        }

        $documents = $this->parsers->parse($format, $documentPayload);

        if ($documents === [] && is_string($payload['raw_text'] ?? null) && $payload['raw_text'] !== '') {
            $documents = [$payload['raw_text']];
        }

        $configuration = $source->configuration;
        $configuration['documents'] = $documents;
        $configuration['format'] = $format;

        if (isset($payload['chunk_size'])) {
            $configuration['chunk_size'] = (int) $payload['chunk_size'];
        }

        if (isset($payload['chunk_overlap'])) {
            $configuration['chunk_overlap'] = (int) $payload['chunk_overlap'];
        }

        if (isset($payload['tenant'])) {
            $configuration['tenant'] = (string) $payload['tenant'];
        }

        $updated = $this->sources->save(new KnowledgeSourceDefinition(
            slug: $source->slug,
            name: $source->name,
            driver: $source->driver,
            configuration: $configuration,
            status: $source->status,
            metadata: $source->metadata,
        ));

        if ($reindex) {
            $this->dispatchReindex($updated->slug);
        }

        return $updated;
    }

    private function dispatchReindex(string $slug): void
    {
        if ((bool) config('agentic.knowledge.queue_reindex', false)) {
            ReindexKnowledgeSourceJob::dispatch($slug);

            return;
        }

        $source = $this->sources->findBySlug($slug);

        if ($source !== null) {
            $this->orchestrator->reindex($source);
        }
    }

    /**
     * @return array{documents: int, chunks: int}
     */
    public function preview(KnowledgeSourceDefinition $source): array
    {
        $documents = $this->collector->collect($source);

        return [
            'documents' => count(is_array($source->configuration['documents'] ?? null) ? $source->configuration['documents'] : []),
            'chunks' => count($documents),
        ];
    }
}
