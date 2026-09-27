<?php

namespace Agentic\Http\Controllers\Admin\Web;

use Agentic\Models\Execution;
use Illuminate\Contracts\View\View;

final class ExecutionWebController
{
    public function index(): View
    {
        return view('agentic::admin.executions.index', [
            'executions' => Execution::query()->orderByDesc('id')->limit(100)->get(),
        ]);
    }

    public function show(int $id): View
    {
        return view('agentic::admin.executions.show', [
            'execution' => Execution::query()->with('steps')->findOrFail($id),
        ]);
    }
}
