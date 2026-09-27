<?php

namespace Agentic\Http\Controllers\Admin;

use Agentic\Admin\Services\KnowledgeSourceAdminService;
use Agentic\Http\Requests\Admin\StoreKnowledgeSourceRequest;
use Agentic\Http\Requests\Admin\UpdateKnowledgeSourceRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

final class KnowledgeSourceController
{
    public function __construct(
        private KnowledgeSourceAdminService $sources,
    ) {}

    public function index(): View
    {
        return view('agentic::admin.knowledge-sources.index', [
            'sources' => $this->sources->list(),
        ]);
    }

    public function create(): View
    {
        return view('agentic::admin.knowledge-sources.create');
    }

    public function store(StoreKnowledgeSourceRequest $request): RedirectResponse
    {
        $source = $this->sources->store($request->toData());

        return redirect()
            ->route('agentic.admin.knowledge-sources.show', $source->slug)
            ->with('status', 'Knowledge source created.');
    }

    public function show(string $slug): View
    {
        $source = $this->sources->find($slug);

        abort_if($source === null, 404);

        return view('agentic::admin.knowledge-sources.show', [
            'source' => $source,
        ]);
    }

    public function edit(string $slug): View
    {
        $source = $this->sources->find($slug);

        abort_if($source === null, 404);

        return view('agentic::admin.knowledge-sources.edit', [
            'source' => $source,
        ]);
    }

    public function update(UpdateKnowledgeSourceRequest $request, string $slug): RedirectResponse
    {
        abort_if($this->sources->find($slug) === null, 404);

        $this->sources->update($slug, $request->toData());

        return redirect()
            ->route('agentic.admin.knowledge-sources.show', $slug)
            ->with('status', 'Knowledge source updated.');
    }

    public function destroy(string $slug): RedirectResponse
    {
        if (! $this->sources->delete($slug)) {
            abort(404);
        }

        return redirect()
            ->route('agentic.admin.knowledge-sources.index')
            ->with('status', 'Knowledge source deleted.');
    }

    public function indexDocuments(string $slug): RedirectResponse
    {
        try {
            $this->sources->indexDocuments($slug);
        } catch (\InvalidArgumentException) {
            abort(404);
        }

        return redirect()
            ->route('agentic.admin.knowledge-sources.show', $slug)
            ->with('status', 'Knowledge source indexed.');
    }
}
