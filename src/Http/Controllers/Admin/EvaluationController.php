<?php

namespace Agentic\Http\Controllers\Admin;

use Agentic\Http\Requests\Admin\StoreEvaluationRequest;
use Agentic\Http\Responses\JsonApiResponse;
use Agentic\Http\Support\AdminPaginator;
use Agentic\Models\Evaluation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class EvaluationController
{
    public function index(Request $request): JsonResponse
    {
        $rows = Evaluation::query()->orderByDesc('id')->limit(200)->get()->all();

        return response()->json(AdminPaginator::paginate($rows, $request));
    }

    public function store(StoreEvaluationRequest $request): JsonResponse
    {
        $row = Evaluation::query()->create($request->validated());

        return JsonApiResponse::created($row);
    }
}
