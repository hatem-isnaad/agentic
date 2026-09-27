<?php

namespace Agentic\Knowledge;

final readonly class KnowledgeChunk
{
    public function __construct(
        public string $content,
        public string $source,
        public ?float $score = null,
        public array $metadata = [],
    ) {}
}
