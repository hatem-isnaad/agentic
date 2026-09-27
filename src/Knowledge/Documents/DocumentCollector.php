<?php

namespace Agentic\Knowledge\Documents;

use Agentic\Knowledge\Chunking\TextChunker;
use Agentic\Knowledge\KnowledgeSourceDefinition;

final class DocumentCollector
{
    public function __construct(
        private TextChunker $chunker = new TextChunker(),
    ) {}

    /**
     * @return list<string>
     */
    public function collect(KnowledgeSourceDefinition $source): array
    {
        $config = $source->configuration;
        $chunkSize = max(100, (int) ($config['chunk_size'] ?? config('agentic.knowledge.chunk_size', 800)));
        $chunkOverlap = max(0, (int) ($config['chunk_overlap'] ?? config('agentic.knowledge.chunk_overlap', 120)));

        $documents = [];

        if (is_string($config['raw_text'] ?? null) && $config['raw_text'] !== '') {
            $documents = array_merge($documents, $this->chunker->chunk($config['raw_text'], $chunkSize, $chunkOverlap));
        }

        $entries = $config['documents'] ?? [];

        if (! is_array($entries)) {
            return array_values(array_unique(array_filter($documents, fn (string $doc) => $doc !== '')));
        }

        foreach ($entries as $document) {
            if (is_string($document) && $document !== '') {
                $documents = array_merge($documents, $this->chunker->chunk($document, $chunkSize, $chunkOverlap));
                continue;
            }

            if (! is_array($document)) {
                continue;
            }

            $content = (string) ($document['content'] ?? '');
            $title = (string) ($document['title'] ?? '');

            if ($content === '') {
                continue;
            }

            $payload = $title !== '' ? $title."\n\n".$content : $content;
            $documents = array_merge($documents, $this->chunker->chunk($payload, $chunkSize, $chunkOverlap));
        }

        return array_values(array_filter($documents, fn (string $doc) => trim($doc) !== ''));
    }
}
