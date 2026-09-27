<?php

namespace Agentic\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;

final class SkillRoutingSchemaController
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'data' => [
                'fields' => [
                    [
                        'key' => 'routing_keywords',
                        'type' => 'string[]',
                        'label' => 'Skill routing keywords',
                        'description' => 'Deterministic keywords used by SkillRouter to include this skill for an agent run (merged with config keyword_map).',
                        'example' => ['invoice', 'payment', 'refund'],
                    ],
                ],
                'skill_routing' => [
                    'enabled' => config('agentic.skill_routing.enabled', true),
                    'fallback' => config('agentic.skill_routing.fallback', 'all'),
                    'lexical_enabled' => config('agentic.skill_routing.lexical.enabled', true),
                    'llm_enabled' => config('agentic.skill_routing.llm.enabled', false),
                    'pipeline' => ['skill_hint', 'keyword', 'lexical', 'llm_optional'],
                ],
            ],
        ]);
    }
}
