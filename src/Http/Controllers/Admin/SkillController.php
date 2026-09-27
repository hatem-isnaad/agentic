<?php

namespace Agentic\Http\Controllers\Admin;

use Agentic\Admin\Services\SkillAdminService;
use Agentic\Http\Requests\Admin\StoreSkillRequest;
use Agentic\Http\Requests\Admin\UpdateSkillRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

final class SkillController
{
    public function __construct(
        private SkillAdminService $skills,
    ) {}

    public function index(): View
    {
        return view('agentic::admin.skills.index', [
            'skills' => $this->skills->list(),
        ]);
    }

    public function create(): View
    {
        return view('agentic::admin.skills.create');
    }

    public function store(StoreSkillRequest $request): RedirectResponse
    {
        $skill = $this->skills->store($request->toData());

        return redirect()
            ->route('agentic.admin.skills.show', $skill->slug)
            ->with('status', 'Skill created.');
    }

    public function show(string $slug): View
    {
        $skill = $this->skills->find($slug);

        abort_if($skill === null, 404);

        return view('agentic::admin.skills.show', [
            'skill' => $skill,
        ]);
    }

    public function edit(string $slug): View
    {
        $skill = $this->skills->find($slug);

        abort_if($skill === null, 404);

        return view('agentic::admin.skills.edit', [
            'skill' => $skill,
        ]);
    }

    public function update(UpdateSkillRequest $request, string $slug): RedirectResponse
    {
        abort_if($this->skills->find($slug) === null, 404);

        $this->skills->update($slug, $request->toData());

        return redirect()
            ->route('agentic.admin.skills.show', $slug)
            ->with('status', 'Skill updated.');
    }

    public function destroy(string $slug): RedirectResponse
    {
        if (! $this->skills->delete($slug)) {
            abort(404);
        }

        return redirect()
            ->route('agentic.admin.skills.index')
            ->with('status', 'Skill deleted.');
    }
}
