<?php

namespace Agentic\Memory;

final readonly class MemoryRecord
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public ?int $id,
        public string $scope,
        public string $scopeKey,
        public ?string $agentSlug,
        public string $key,
        public string $content,
        public int $importance = 5,
        public array $metadata = [],
        public ?\DateTimeInterface $expiresAt = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toContextArray(): array
    {
        return [
            'scope' => $this->scope,
            'key' => $this->key,
            'content' => $this->content,
            'importance' => $this->importance,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'scope' => $this->scope,
            'scope_key' => $this->scopeKey,
            'agent_slug' => $this->agentSlug,
            'key' => $this->key,
            'content' => $this->content,
            'importance' => $this->importance,
            'metadata' => $this->metadata,
            'expires_at' => $this->expiresAt?->format(DATE_ATOM),
        ];
    }
}
