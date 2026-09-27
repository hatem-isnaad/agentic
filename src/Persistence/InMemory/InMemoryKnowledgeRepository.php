<?php

namespace Agentic\Persistence\InMemory;

use Agentic\Contracts\Repositories\KnowledgeRepository;
use Agentic\Knowledge\KnowledgeSourceDefinition;

final class InMemoryKnowledgeRepository implements KnowledgeRepository
{
    /** @var array<string, KnowledgeSourceDefinition> */
    private array $sources = [];

    public function seed(KnowledgeSourceDefinition ...$sources): void
    {
        foreach ($sources as $source) {
            $this->sources[$source->slug] = $source;
        }
    }

    public function findBySlug(string $slug): ?KnowledgeSourceDefinition
    {
        return $this->sources[$slug] ?? null;
    }

    public function allPublished(): array
    {
        return array_values(array_filter(
            $this->sources,
            fn (KnowledgeSourceDefinition $source) => ($source->status ?? 'published') === 'published',
        ));
    }

    public function all(): array
    {
        return array_values($this->sources);
    }

    public function save(KnowledgeSourceDefinition $source): KnowledgeSourceDefinition
    {
        $this->sources[$source->slug] = $source;

        return $source;
    }

    public function delete(string $slug): bool
    {
        if (! isset($this->sources[$slug])) {
            return false;
        }

        unset($this->sources[$slug]);

        return true;
    }
}
