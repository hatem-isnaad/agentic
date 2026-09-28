<?php

namespace Agentic\Http\Controllers\Admin;

use Agentic\Evals\EvalSetRunner;
use Agentic\Http\Responses\JsonApiResponse;
use Agentic\Http\Support\AdminPaginator;
use Agentic\Models\EvalSet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class EvalSetController
{
    public function index(Request $request): JsonResponse
    {
        $rows = EvalSet::query()->withCount('cases')->orderBy('name')->get()->all();

        return response()->json(AdminPaginator::paginate($rows, $request));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:80', 'unique:agentic_eval_sets,slug'],
            'agent_slug' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);
        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['name']);

        return JsonApiResponse::created(EvalSet::query()->create($validated));
    }

    public function show(string $slug): JsonResponse
    {
        $set = EvalSet::query()->where('slug', $slug)->with(['cases', 'runs.results'])->first();
        if ($set === null) {
            return JsonApiResponse::error('Eval set not found.', 404);
        }

        return JsonApiResponse::data($set);
    }

    public function addCase(Request $request, string $slug): JsonResponse
    {
        $set = EvalSet::query()->where('slug', $slug)->first();
        if ($set === null) {
            return JsonApiResponse::error('Eval set not found.', 404);
        }

        $validated = $request->validate([
            'question' => ['required', 'string', 'max:4000'],
            'expect_contains' => ['nullable', 'array'],
            'expect_contains.*' => ['string', 'max:200'],
            'expect_tool' => ['nullable', 'string', 'max:120'],
        ]);

        return JsonApiResponse::created($set->cases()->create($validated));
    }

    public function run(string $slug, EvalSetRunner $runner): JsonResponse
    {
        $set = EvalSet::query()->where('slug', $slug)->with('cases')->first();
        if ($set === null) {
            return JsonApiResponse::error('Eval set not found.', 404);
        }

        $run = $runner->run($set);

        return JsonApiResponse::data($run->load('results'));
    }
}
