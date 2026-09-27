<?php

namespace Agentic\Admin\Services;

use Agentic\Admin\DTO\KnowledgeSourceData;
use Agentic\Contracts\Repositories\KnowledgeRepository;
use Agentic\Knowledge\KnowledgeOrchestrator;
use Agentic\Knowledge\KnowledgeSourceDefinition;

final class KnowledgeSourceAdminService
{
    public function __construct(
        private KnowledgeRepository $sources,
        private KnowledgeOrchestrator $knowledge,
    ) {}

    /**
     * @return list<KnowledgeSourceData>
     */
    public function list(): array
    {
        return array_map(
            fn ($source) => KnowledgeSourceData::fromDefinition($source),
            $this->sources->all(),
        );
    }

    public function find(string $slug): ?KnowledgeSourceData
    {
        $source = $this->sources->findBySlug($slug);

        return $source ? KnowledgeSourceData::fromDefinition($source) : null;
    }

    public function store(KnowledgeSourceData $data): KnowledgeSourceData
    {
        $saved = $this->sources->save($data->toDefinition());

        return KnowledgeSourceData::fromDefinition($saved);
    }

    public function update(string $slug, KnowledgeSourceData $data): KnowledgeSourceData
    {
        $definition = $data->toDefinition();

        if ($definition->slug !== $slug) {
            $definition = new KnowledgeSourceDefinition(
                slug: $slug,
                name: $definition->name,
                driver: $definition->driver,
                configuration: $definition->configuration,
                status: $definition->status,
                metadata: $definition->metadata,
            );
        }

        $saved = $this->sources->save($definition);

        return KnowledgeSourceData::fromDefinition($saved);
    }

    public function delete(string $slug): bool
    {
        return $this->sources->delete($slug);
    }

    public function indexDocuments(string $slug): void
    {
        $source = $this->sources->findBySlug($slug);

        if ($source === null) {
            throw new \InvalidArgumentException("Knowledge source [{$slug}] not found.");
        }

        $this->knowledge->index($source);
    }
}
