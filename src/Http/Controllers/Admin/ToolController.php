<?php

namespace Agentic\Http\Controllers\Admin;

use Agentic\Admin\Services\ToolAdminService;
use Agentic\Http\Requests\Admin\StoreToolRequest;
use Agentic\Http\Requests\Admin\UpdateToolRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

final class ToolController
{
    public function __construct(
        private ToolAdminService $tools,
    ) {}

    public function index(): View
    {
        return view('agentic::admin.tools.index', [
            'tools' => $this->tools->list(),
        ]);
    }

    public function create(): View
    {
        return view('agentic::admin.tools.create');
    }

    public function store(StoreToolRequest $request): RedirectResponse
    {
        $tool = $this->tools->store($request->toData());

        return redirect()
            ->route('agentic.admin.tools.show', $tool->slug)
            ->with('status', 'Tool created.');
    }

    public function show(string $slug): View
    {
        $tool = $this->tools->find($slug);

        abort_if($tool === null, 404);

        return view('agentic::admin.tools.show', [
            'tool' => $tool,
        ]);
    }

    public function edit(string $slug): View
    {
        $tool = $this->tools->find($slug);

        abort_if($tool === null, 404);

        return view('agentic::admin.tools.edit', [
            'tool' => $tool,
        ]);
    }

    public function update(UpdateToolRequest $request, string $slug): RedirectResponse
    {
        abort_if($this->tools->find($slug) === null, 404);

        $this->tools->update($slug, $request->toData());

        return redirect()
            ->route('agentic.admin.tools.show', $slug)
            ->with('status', 'Tool updated.');
    }

    public function destroy(string $slug): RedirectResponse
    {
        if (! $this->tools->delete($slug)) {
            abort(404);
        }

        return redirect()
            ->route('agentic.admin.tools.index')
            ->with('status', 'Tool deleted.');
    }
}
