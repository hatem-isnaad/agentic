<?php

namespace Agentic\Knowledge\Indexers;

use Agentic\Knowledge\Contracts\Indexer;
use Agentic\Knowledge\KnowledgeSourceDefinition;

/**
 * Array sources are pre-indexed in configuration — nothing to do at index time.
 */
final class ArrayKnowledgeIndexer implements Indexer
{
    public function supports(KnowledgeSourceDefinition $source): bool
    {
        return $source->driver === 'array';
    }

    public function index(KnowledgeSourceDefinition $source): void
    {
        unset($source);
    }
}
