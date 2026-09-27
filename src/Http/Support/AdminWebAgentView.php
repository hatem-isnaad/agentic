<?php

namespace Agentic\Http\Support;

use Agentic\Models\Agent;

final class AdminWebAgentView
{
    public static function fromModel(?Agent $agent): ?object
    {
        if ($agent === null) {
            return null;
        }

        $modelConfig = is_array($agent->model_config) ? $agent->model_config : [];
        $config = is_array($agent->config) ? $agent->config : [];

        return (object) [
            'name' => $agent->name,
            'slug' => $agent->slug,
            'description' => $agent->description,
            'instructions' => $agent->instructions,
            'status' => $agent->status instanceof \BackedEnum ? $agent->status->value : (string) $agent->status,
            'provider' => $modelConfig['provider'] ?? '',
            'model' => $modelConfig['model'] ?? '',
            'skills' => $agent->relationLoaded('skills')
                ? $agent->skills->pluck('slug')->all()
                : [],
            'tools' => is_array($config['tools'] ?? null) ? $config['tools'] : [],
        ];
    }
}
