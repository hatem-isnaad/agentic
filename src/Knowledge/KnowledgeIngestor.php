<?php

namespace Agentic\Knowledge;

use Agentic\Contracts\Repositories\KnowledgeRepository;
use Agentic\Knowledge\Documents\DocumentCollector;
use Agentic\Knowledge\Documents\DocumentParserResolver;

final class KnowledgeIngestor
{
    public function __construct(
        private KnowledgeRepository $sources,
        private KnowledgeOrchestrator $orchestrator,
        private DocumentParserResolver $parsers = new DocumentParserResolver(),
        private DocumentCollector $collector = new DocumentCollector(),
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
        $documents = $this->parsers->parse(
            $format,
            $payload['documents'] ?? ($payload['raw_text'] ?? $payload['content'] ?? []),
        );

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
            $this->orchestrator->reindex($updated);
        }

        return $updated;
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
