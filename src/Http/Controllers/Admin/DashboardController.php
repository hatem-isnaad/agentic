<?php

namespace Agentic\Http\Controllers\Admin;

use Agentic\Admin\Services\DashboardAdminService;
use Illuminate\Contracts\View\View;

final class DashboardController
{
    public function __construct(
        private DashboardAdminService $dashboard,
    ) {}

    public function __invoke(): View
    {
        return view('agentic::admin.dashboard', [
            'stats' => $this->dashboard->stats(),
        ]);
    }
}
