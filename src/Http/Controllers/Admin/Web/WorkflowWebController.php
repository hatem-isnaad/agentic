<?php

namespace Agentic\Http\Controllers\Admin\Web;

use Agentic\Admin\Services\WorkflowAdminService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class WorkflowWebController
{
    public function __construct(
        private WorkflowAdminService $workflows,
    ) {}

    public function index(): View
    {
        return view('agentic::admin.workflows.index', [
            'workflows' => $this->workflows->list(),
        ]);
    }

    public function create(): View
    {
        return view('agentic::admin.workflows.create', [
            'workflow' => null,
            'stepsJson' => json_encode([
                ['id' => 'done', 'type' => 'complete'],
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $payload = $this->validated($request);
        $this->workflows->store($payload);

        return redirect()
            ->route('agentic.admin.workflows.index')
            ->with('status', __('agentic::admin.actions.saved'));
    }

    public function show(string $slug): View
    {
        $workflow = $this->workflows->find($slug);

        abort_if($workflow === null, 404);

        return view('agentic::admin.workflows.show', [
            'workflow' => $workflow,
        ]);
    }

    public function edit(string $slug): View
    {
        $workflow = $this->workflows->find($slug);

        abort_if($workflow === null, 404);

        return view('agentic::admin.workflows.edit', [
            'workflow' => $workflow,
            'stepsJson' => json_encode($workflow->steps, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        ]);
    }

    public function update(Request $request, string $slug): RedirectResponse
    {
        abort_if($this->workflows->find($slug) === null, 404);

        $this->workflows->update($slug, $this->validated($request, $slug));

        return redirect()
            ->route('agentic.admin.workflows.show', $slug)
            ->with('status', __('agentic::admin.actions.updated'));
    }

    public function destroy(string $slug): RedirectResponse
    {
        if (! $this->workflows->delete($slug)) {
            abort(404);
        }

        return redirect()
            ->route('agentic.admin.workflows.index')
            ->with('status', __('agentic::admin.actions.deleted'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?string $slug = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'in:draft,published,archived'],
            'steps_json' => ['required', 'string'],
        ]);

        $steps = json_decode($validated['steps_json'], true);

        if (! is_array($steps)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'steps_json' => ['Invalid JSON for workflow steps.'],
            ]);
        }

        $payload = [
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'] ?? 'draft',
            'steps' => $steps,
        ];

        if ($slug !== null) {
            $payload['slug'] = $slug;
        }

        return $payload;
    }
}
