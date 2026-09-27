<?php

namespace Agentic\Http\Controllers\Admin;

use Agentic\Http\Support\AdminLocaleMeta;
use Illuminate\Http\JsonResponse;

final class AiRegistryController
{
    public function __invoke(): JsonResponse
    {
        $configured = config('agentic.ai.providers', []);
        $providers = [];

        foreach ($configured as $key => $meta) {
            if (! is_string($key) || $key === '') {
                continue;
            }

            $providers[] = [
                'key' => $key,
                'label' => is_array($meta) ? (string) ($meta['label'] ?? $key) : $key,
                'models' => is_array($meta) ? array_values($meta['models'] ?? []) : [],
            ];
        }

        return response()->json([
            'data' => [
                'providers' => $providers,
                'default_provider' => config('agentic.ai.provider'),
                'default_model' => config('agentic.ai.model'),
                'persona' => [
                    'genders' => array_values(config('agentic.persona.genders', [])),
                    'languages' => array_values(config('agentic.persona.languages', [])),
                    'dialects' => array_values(config('agentic.persona.dialects', [])),
                    'tones' => array_values(config('agentic.persona.tones', [])),
                    'name_suggestions' => config('agentic.persona.name_suggestions', []),
                ],
            ],
            'meta' => AdminLocaleMeta::build(),
        ]);
    }
}
