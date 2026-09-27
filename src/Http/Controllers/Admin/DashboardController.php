<?php

namespace Agentic\Http\Controllers\Admin;

use Agentic\Admin\Services\DashboardAdminService;
use Agentic\Http\Support\AdminLocaleMeta;
use Illuminate\Http\JsonResponse;

final class DashboardController
{
    public function __construct(
        private DashboardAdminService $dashboard,
    ) {}

    public function __invoke(): JsonResponse
    {
        return response()->json([
            'data' => [
                'stats' => $this->dashboard->stats()->toArray(),
            ],
            'meta' => AdminLocaleMeta::build(),
        ]);
    }
}
