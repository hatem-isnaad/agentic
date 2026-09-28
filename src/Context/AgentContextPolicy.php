<?php

namespace Agentic\Context;

/**
 * Default lean context for every agent turn (admin API, workflows, widget when not overridden).
 *
 * @return array{
 *     enabled: bool,
 *     max_history_messages: int,
 *     skill_routing_limit: int,
 *     skills_fallback_limit: int,
 *     knowledge_chunk_limit: int,
 *     memory_entry_limit: int,
 *     max_tools: int,
 *     compact_skill_descriptions: bool
 * }
 */
final class AgentContextPolicy
{
    public static function resolve(): array
    {
        if (! filter_var(config('agentic.context.lean_enabled', true), FILTER_VALIDATE_BOOL)) {
            return ['enabled' => false];
        }

        $cfg = (array) config('agentic.context', []);

        return [
            'enabled' => true,
            'max_history_messages' => max(0, (int) ($cfg['max_history_messages'] ?? 12)),
            'skill_routing_limit' => max(1, (int) ($cfg['skill_routing_limit'] ?? 4)),
            'skills_fallback_limit' => max(1, (int) ($cfg['skills_fallback_limit'] ?? 4)),
            'knowledge_chunk_limit' => max(0, (int) ($cfg['knowledge_chunk_limit'] ?? 3)),
            'memory_entry_limit' => max(0, (int) ($cfg['memory_entry_limit'] ?? 8)),
            'max_tools' => max(1, (int) ($cfg['max_tools'] ?? 30)),
            'compact_skill_descriptions' => (bool) ($cfg['compact_skill_descriptions'] ?? true),
        ];
    }
}
