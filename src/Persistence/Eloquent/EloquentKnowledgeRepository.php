<?php

namespace Agentic\Persistence\Eloquent;

use Agentic\Contracts\Repositories\KnowledgeRepository;
use Agentic\Enums\Status;
use Agentic\Knowledge\KnowledgeSourceDefinition;
use Agentic\Models\KnowledgeSource;

final class EloquentKnowledgeRepository implements KnowledgeRepository
{
    public function findBySlug(string $slug): ?KnowledgeSourceDefinition
    {
        $model = KnowledgeSource::query()->where('slug', $slug)->first();

        return $model ? $this->toDefinition($model) : null;
    }

    public function allPublished(): array
    {
        return KnowledgeSource::query()
            ->published()
            ->get()
            ->map(fn (KnowledgeSource $model) => $this->toDefinition($model))
            ->all();
    }

    private function toDefinition(KnowledgeSource $model): KnowledgeSourceDefinition
    {
        $config = $model->config ?? [];

        return new KnowledgeSourceDefinition(
            slug: $model->slug,
            name: $model->name,
            driver: (string) ($model->driver ?? 'array'),
            configuration: $config,
            status: $model->status instanceof Status ? $model->status->value : (string) $model->status,
            metadata: [
                'description' => $model->description,
            ],
        );
    }
}
