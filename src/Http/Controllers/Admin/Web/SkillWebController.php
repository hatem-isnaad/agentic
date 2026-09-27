<?php

namespace Agentic\Http\Controllers\Admin\Web;

use Agentic\Admin\Services\SkillAdminService;
use Agentic\Http\Requests\Admin\StoreSkillRequest;
use Agentic\Http\Requests\Admin\UpdateSkillRequest;
use Agentic\Models\Skill;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

final class SkillWebController
{
    public function __construct(
        private SkillAdminService $skills,
    ) {}

    public function index(): View
    {
        return view('agentic::admin.skills.index', [
            'skills' => Skill::query()->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('agentic::admin.skills.create', ['skill' => null]);
    }

    public function store(StoreSkillRequest $request): RedirectResponse
    {
        $this->skills->store($request->toData());

        return redirect()->route('agentic.admin.skills.index')->with('status', 'Saved.');
    }

    public function show(string $slug): View
    {
        return view('agentic::admin.skills.show', [
            'skill' => Skill::query()->where('slug', $slug)->firstOrFail(),
        ]);
    }

    public function edit(string $slug): View
    {
        $skill = Skill::query()->where('slug', $slug)->firstOrFail();
        $config = is_array($skill->config) ? $skill->config : [];
        $skill->tools = is_array($config['tools'] ?? null) ? $config['tools'] : [];

        return view('agentic::admin.skills.edit', compact('skill'));
    }

    public function update(UpdateSkillRequest $request, string $slug): RedirectResponse
    {
        $this->skills->update($slug, $request->toData());

        return redirect()->route('agentic.admin.skills.show', $slug)->with('status', 'Updated.');
    }

    public function destroy(string $slug): RedirectResponse
    {
        $this->skills->delete($slug);

        return redirect()->route('agentic.admin.skills.index')->with('status', 'Deleted.');
    }
}
