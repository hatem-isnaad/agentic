<?php

namespace Agentic\Http\Controllers\Admin\Web;

use Agentic\Admin\Services\ToolAdminService;
use Agentic\Http\Requests\Admin\StoreToolRequest;
use Agentic\Http\Requests\Admin\UpdateToolRequest;
use Agentic\Models\Tool;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

final class ToolWebController
{
    public function __construct(
        private ToolAdminService $tools,
    ) {}

    public function index(): View
    {
        return view('agentic::admin.tools.index', [
            'tools' => Tool::query()->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('agentic::admin.tools.create', ['tool' => null]);
    }

    public function store(StoreToolRequest $request): RedirectResponse
    {
        $this->tools->store($request->toData());

        return redirect()->route('agentic.admin.tools.index')->with('status', 'Saved.');
    }

    public function show(string $slug): View
    {
        return view('agentic::admin.tools.show', [
            'tool' => Tool::query()->where('slug', $slug)->firstOrFail(),
        ]);
    }

    public function edit(string $slug): View
    {
        return view('agentic::admin.tools.edit', [
            'tool' => Tool::query()->where('slug', $slug)->firstOrFail(),
        ]);
    }

    public function update(UpdateToolRequest $request, string $slug): RedirectResponse
    {
        $this->tools->update($slug, $request->toData());

        return redirect()->route('agentic.admin.tools.show', $slug)->with('status', 'Updated.');
    }

    public function destroy(string $slug): RedirectResponse
    {
        $this->tools->delete($slug);

        return redirect()->route('agentic.admin.tools.index')->with('status', 'Deleted.');
    }
}
