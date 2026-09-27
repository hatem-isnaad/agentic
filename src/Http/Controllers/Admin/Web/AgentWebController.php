<?php

namespace Agentic\Http\Controllers\Admin\Web;

use Agentic\Admin\Services\AgentAdminService;
use Agentic\Http\Requests\Admin\StoreAgentRequest;
use Agentic\Http\Requests\Admin\UpdateAgentRequest;
use Agentic\Http\Support\AdminWebAgentView;
use Agentic\Models\Agent;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

final class AgentWebController
{
    public function __construct(
        private AgentAdminService $agents,
    ) {}

    public function index(): View
    {
        return view('agentic::admin.agents.index', [
            'agents' => Agent::query()->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('agentic::admin.agents.create', ['agent' => null]);
    }

    public function store(StoreAgentRequest $request): RedirectResponse
    {
        $this->agents->store($request->toData());

        return redirect()
            ->route('agentic.admin.agents.index')
            ->with('status', __('agentic::admin.actions.saved'));
    }

    public function show(string $slug): View
    {
        $model = Agent::query()->where('slug', $slug)->firstOrFail();

        return view('agentic::admin.agents.show', ['agent' => $model]);
    }

    public function edit(string $slug): View
    {
        $model = Agent::query()->with('skills')->where('slug', $slug)->firstOrFail();

        return view('agentic::admin.agents.edit', [
            'agent' => AdminWebAgentView::fromModel($model),
        ]);
    }

    public function update(UpdateAgentRequest $request, string $slug): RedirectResponse
    {
        $this->agents->update($slug, $request->toData());

        return redirect()
            ->route('agentic.admin.agents.show', $slug)
            ->with('status', __('agentic::admin.actions.updated'));
    }

    public function destroy(string $slug): RedirectResponse
    {
        $this->agents->delete($slug);

        return redirect()
            ->route('agentic.admin.agents.index')
            ->with('status', __('agentic::admin.actions.deleted'));
    }
}
