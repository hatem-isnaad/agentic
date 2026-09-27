<?php

namespace Agentic\Http\Controllers\Admin\Web;

use Agentic\Models\Agent;
use Agentic\Widget\Services\WidgetSettingsService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class WidgetSettingsWebController
{
    public function __construct(
        private WidgetSettingsService $settings,
    ) {}

    public function index(): View
    {
        return view('agentic::admin.widget-settings.index', [
            'agents' => Agent::query()->orderBy('name')->get(),
            'overrides' => collect($this->settings->all())->keyBy('agent_slug'),
        ]);
    }

    public function edit(string $agentSlug): View
    {
        Agent::query()->where('slug', $agentSlug)->firstOrFail();

        $merged = $this->settings->mergedForAgent($agentSlug);

        return view('agentic::admin.widget-settings.edit', [
            'agentSlug' => $agentSlug,
            'settingsJson' => json_encode($merged, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        ]);
    }

    public function update(Request $request, string $agentSlug): RedirectResponse
    {
        Agent::query()->where('slug', $agentSlug)->firstOrFail();

        $validated = $request->validate([
            'settings_json' => ['required', 'string'],
        ]);

        $settings = json_decode($validated['settings_json'], true);

        if (! is_array($settings)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'settings_json' => ['Invalid JSON.'],
            ]);
        }

        $this->settings->upsert($agentSlug, $settings);

        return redirect()
            ->route('agentic.admin.widget-settings.edit', $agentSlug)
            ->with('status', __('agentic::admin.actions.updated'));
    }

    public function destroy(string $agentSlug): RedirectResponse
    {
        $this->settings->delete($agentSlug);

        return redirect()
            ->route('agentic.admin.widget-settings.index')
            ->with('status', __('agentic::admin.actions.deleted'));
    }
}
