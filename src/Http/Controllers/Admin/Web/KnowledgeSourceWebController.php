<?php

namespace Agentic\Http\Controllers\Admin\Web;

use Agentic\Admin\Services\KnowledgeSourceAdminService;
use Agentic\Http\Requests\Admin\StoreKnowledgeSourceRequest;
use Agentic\Http\Requests\Admin\UpdateKnowledgeSourceRequest;
use Agentic\Knowledge\KnowledgeOrchestrator;
use Agentic\Models\KnowledgeSource;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

final class KnowledgeSourceWebController
{
    public function __construct(
        private KnowledgeSourceAdminService $sources,
        private KnowledgeOrchestrator $knowledge,
    ) {}

    public function index(): View
    {
        return view('agentic::admin.knowledge-sources.index', [
            'sources' => KnowledgeSource::query()->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('agentic::admin.knowledge-sources.create', ['source' => null]);
    }

    public function store(StoreKnowledgeSourceRequest $request): RedirectResponse
    {
        $this->sources->store($request->toData());

        return redirect()->route('agentic.admin.knowledge-sources.index')->with('status', 'Saved.');
    }

    public function show(string $slug): View
    {
        return view('agentic::admin.knowledge-sources.show', [
            'source' => KnowledgeSource::query()->where('slug', $slug)->firstOrFail(),
        ]);
    }

    public function edit(string $slug): View
    {
        return view('agentic::admin.knowledge-sources.edit', [
            'source' => KnowledgeSource::query()->where('slug', $slug)->firstOrFail(),
        ]);
    }

    public function update(UpdateKnowledgeSourceRequest $request, string $slug): RedirectResponse
    {
        $this->sources->update($slug, $request->toData());

        return redirect()->route('agentic.admin.knowledge-sources.show', $slug)->with('status', 'Updated.');
    }

    public function destroy(string $slug): RedirectResponse
    {
        $this->sources->delete($slug);

        return redirect()->route('agentic.admin.knowledge-sources.index')->with('status', 'Deleted.');
    }

    public function indexDocuments(string $slug): RedirectResponse
    {
        $source = $this->sources->find($slug);

        if ($source === null) {
            abort(404);
        }

        $this->knowledge->reindex($source->toDefinition());

        return back()->with('status', __('agentic::admin.actions.index_documents'));
    }
}
