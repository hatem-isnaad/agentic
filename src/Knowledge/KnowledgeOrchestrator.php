<?php

namespace Agentic\Knowledge;

use Agentic\Agent\AgentDefinition;
use Agentic\Context\RuntimeContext;
use Agentic\Contracts\Repositories\KnowledgeRepository;
use Agentic\Skill\SkillResolver;

/**
 * Orchestrates retrieval from configured knowledge sources.
 *
 * Agentic does not implement vector DB protocols directly — it uses contracts.
 */
final class KnowledgeOrchestrator
{
    /** @var list<\Agentic\Knowledge\Contracts\Retriever> */
    private array $retrievers = [];

    /** @var list<\Agentic\Knowledge\Contracts\Indexer> */
    private array $indexers = [];

    public function __construct(
        private ?KnowledgeRepository $repository = null,
        private ?SkillResolver $skills = null,
    ) {}

    public function extendRetriever(\Agentic\Knowledge\Contracts\Retriever $retriever): void
    {
        $this->retrievers[] = $retriever;
    }

    public function extendIndexer(\Agentic\Knowledge\Contracts\Indexer $indexer): void
    {
        $this->indexers[] = $indexer;
    }

    public function index(KnowledgeSourceDefinition $source): void
    {
        foreach ($this->indexers as $indexer) {
            if ($indexer->supports($source)) {
                $indexer->index($source);

                return;
            }
        }
    }

    /**
     * @return list<KnowledgeChunk>
     */
    public function retrieveForAgent(
        AgentDefinition $agent,
        string $query,
        ?RuntimeContext $runtime = null,
        int $limit = 5,
    ): array {
        $sourceSlugs = $this->collectSourceSlugs($agent);

        return $this->retrieveFromSources($sourceSlugs, $query, $runtime, $limit);
    }

    /**
     * @param  list<string>  $sourceSlugs
     * @return list<KnowledgeChunk>
     */
    public function retrieveFromSources(
        array $sourceSlugs,
        string $query,
        ?RuntimeContext $runtime = null,
        int $limit = 5,
    ): array {
        if ($this->repository === null || $query === '') {
            return [];
        }

        $chunks = [];

        foreach (array_unique($sourceSlugs) as $slug) {
            if (! is_string($slug) || $slug === '') {
                continue;
            }

            $source = $this->repository->findBySlug($slug);

            if ($source === null) {
                continue;
            }

            foreach ($this->retrievers as $retriever) {
                if ($retriever->supports($source)) {
                    $chunks = array_merge(
                        $chunks,
                        $retriever->retrieve($source, $query, $limit, $runtime),
                    );
                    break;
                }
            }
        }

        usort($chunks, fn (KnowledgeChunk $a, KnowledgeChunk $b) => ($b->score ?? 0) <=> ($a->score ?? 0));

        return array_slice($chunks, 0, $limit);
    }

    /**
     * @return list<string>
     */
    private function collectSourceSlugs(AgentDefinition $agent): array
    {
        $slugs = $this->extractSourceSlugs($agent->knowledge);

        if ($this->skills !== null) {
            foreach ($this->skills->resolveMany($agent->skills) as $skill) {
                $slugs = array_merge($slugs, $this->extractSourceSlugs($skill->knowledge));
            }
        }

        return array_values(array_unique($slugs));
    }

    /**
     * @param  list<mixed>  $entries
     * @return list<string>
     */
    private function extractSourceSlugs(array $entries): array
    {
        $slugs = [];

        foreach ($entries as $entry) {
            if (is_string($entry) && $entry !== '') {
                $slugs[] = $entry;
            } elseif (is_array($entry) && is_string($entry['source'] ?? null)) {
                $slugs[] = $entry['source'];
            }
        }

        return $slugs;
    }
}
