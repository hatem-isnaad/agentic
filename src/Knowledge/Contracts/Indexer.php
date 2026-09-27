<?php

namespace Agentic\Knowledge\Contracts;

use Agentic\Knowledge\KnowledgeSourceDefinition;

/**
 * Indexes a knowledge source into retrievable storage.
 */
interface Indexer
{
    public function supports(KnowledgeSourceDefinition $source): bool;

    public function index(KnowledgeSourceDefinition $source): void;
}
