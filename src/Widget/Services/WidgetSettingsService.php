<?php

namespace Agentic\Widget\Services;

use Agentic\Models\WidgetSetting;
use Illuminate\Support\Arr;

final class WidgetSettingsService
{
    /**
     * @return array<string, mixed>
     */
    public function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'auth_mode' => ['type' => 'string', 'enum' => ['guest', 'auth', 'both']],
                'locale' => ['type' => 'string'],
                'agent_language' => ['type' => 'string'],
                'intake_enabled' => ['type' => 'boolean'],
                'welcome_message' => ['type' => 'string'],
                'intake_questions' => ['type' => 'array', 'items' => ['type' => 'string']],
                'theme' => ['type' => 'object'],
                'reply_formats' => ['type' => 'array', 'items' => ['type' => 'string']],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function mergedForAgent(string $agentSlug): array
    {
        $base = config('agentic.widget', []);
        $agentOverrides = $base['agents'][$agentSlug] ?? [];
        $record = WidgetSetting::query()->where('agent_slug', $agentSlug)->first();
        $db = is_array($record?->settings) ? $record->settings : [];

        return array_replace_recursive(
            [
                'auth_mode' => $base['auth']['mode'] ?? 'both',
                'locale' => $base['locale']['default'] ?? 'en',
                'agent_language' => $base['locale']['agent_language'] ?? null,
                'intake_enabled' => $base['intake']['enabled'] ?? false,
                'welcome_message' => $base['intake']['welcome_message'] ?? null,
                'intake_questions' => $base['intake']['questions'] ?? [],
                'theme' => $base['theme'] ?? [],
                'reply_formats' => $base['reply']['formats'] ?? ['blocks'],
            ],
            $agentOverrides,
            $db,
        );
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    public function upsert(string $agentSlug, array $settings): WidgetSetting
    {
        return WidgetSetting::query()->updateOrCreate(
            ['agent_slug' => $agentSlug],
            ['settings' => $settings],
        );
    }

    public function delete(string $agentSlug): bool
    {
        return WidgetSetting::query()->where('agent_slug', $agentSlug)->delete() > 0;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function all(): array
    {
        return WidgetSetting::query()
            ->orderBy('agent_slug')
            ->get()
            ->map(fn (WidgetSetting $row) => [
                'agent_slug' => $row->agent_slug,
                'settings' => $row->settings ?? [],
                'updated_at' => optional($row->updated_at)?->toISOString(),
            ])
            ->all();
    }
}
