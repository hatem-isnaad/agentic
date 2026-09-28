<?php

namespace Agentic\Knowledge\Support;

use Agentic\Knowledge\KnowledgeSourceDefinition;

final class KnowledgeNamespace
{
    public static function forSource(KnowledgeSourceDefinition $source): string
    {
        $base = $source->configuration['namespace'] ?? null;

        return is_string($base) && $base !== '' ? $base : $source->slug;
    }
}
