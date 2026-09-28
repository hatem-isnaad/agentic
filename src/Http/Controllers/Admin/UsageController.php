<?php

namespace Agentic\Http\Controllers\Admin;

use Agentic\Admin\Services\UsageAdminService;
use Agentic\Http\Support\AdminLocaleMeta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class UsageController
{
    public function __construct(private UsageAdminService $usage) {}

    public function __invoke(Request $request): JsonResponse
    {
        $limit = min(2000, max(50, (int) $request->query('limit', 400)));

        return response()->json([
            'data' => $this->usage->summary($limit)->toArray(),
            'meta' => AdminLocaleMeta::build(),
        ]);
    }
}
