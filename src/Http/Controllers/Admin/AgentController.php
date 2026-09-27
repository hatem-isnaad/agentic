<?php

namespace Agentic\Http\Controllers\Admin;

use Agentic\Admin\Services\AgentAdminService;
use Agentic\Http\Requests\Admin\StoreAgentRequest;
use Agentic\Http\Requests\Admin\UpdateAgentRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

final class AgentController
{
    public function __construct(
        private AgentAdminService $agents,
    ) {}

    public function index(): View
    {
        return view('agentic::admin.agents.index', [
            'agents' => $this->agents->list(),
        ]);
    }

    public function create(): View
    {
        return view('agentic::admin.agents.create');
    }

    public function store(StoreAgentRequest $request): RedirectResponse
    {
        $agent = $this->agents->store($request->toData());

        return redirect()
            ->route('agentic.admin.agents.show', $agent->slug)
            ->with('status', 'Agent created.');
    }

    public function show(string $slug): View
    {
        $agent = $this->agents->find($slug);

        abort_if($agent === null, 404);

        return view('agentic::admin.agents.show', [
            'agent' => $agent,
        ]);
    }

    public function edit(string $slug): View
    {
        $agent = $this->agents->find($slug);

        abort_if($agent === null, 404);

        return view('agentic::admin.agents.edit', [
            'agent' => $agent,
        ]);
    }

    public function update(UpdateAgentRequest $request, string $slug): RedirectResponse
    {
        abort_if($this->agents->find($slug) === null, 404);

        $this->agents->update($slug, $request->toData());

        return redirect()
            ->route('agentic.admin.agents.show', $slug)
            ->with('status', 'Agent updated.');
    }

    public function destroy(string $slug): RedirectResponse
    {
        if (! $this->agents->delete($slug)) {
            abort(404);
        }

        return redirect()
            ->route('agentic.admin.agents.index')
            ->with('status', 'Agent deleted.');
    }
}
