<?php

namespace Agentic\Widget\Support;

/**
 * Lean context defaults for embed widget turns (tokens, latency).
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
final class WidgetContextPolicy
{
    public static function resolve(): array
    {
        $cfg = (array) config('agentic.widget.context', []);

        return [
            'enabled' => (bool) ($cfg['enabled'] ?? true),
            'max_history_messages' => max(0, (int) ($cfg['max_history_messages'] ?? 12)),
            'skill_routing_limit' => max(1, (int) ($cfg['skill_routing_limit'] ?? 2)),
            'skills_fallback_limit' => max(1, (int) ($cfg['skills_fallback_limit'] ?? 2)),
            'knowledge_chunk_limit' => max(0, (int) ($cfg['knowledge_chunk_limit'] ?? 3)),
            'memory_entry_limit' => max(0, (int) ($cfg['memory_entry_limit'] ?? 8)),
            'max_tools' => max(1, (int) ($cfg['max_tools'] ?? 20)),
            'compact_skill_descriptions' => (bool) ($cfg['compact_skill_descriptions'] ?? true),
        ];
    }
}
