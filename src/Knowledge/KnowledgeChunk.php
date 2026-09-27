<?php

namespace Agentic\Knowledge;

/**
 * A retrieved or indexed knowledge fragment.
 */
final readonly class KnowledgeChunk
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $content,
        public ?string $source = null,
        public ?float $score = null,
        public array $metadata = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toContextArray(): array
    {
        return array_filter([
            'content' => $this->content,
            'source' => $this->source,
            'score' => $this->score,
            'metadata' => $this->metadata === [] ? null : $this->metadata,
        ], fn ($value) => $value !== null);
    }
}
