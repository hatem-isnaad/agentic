<?php

namespace Agentic\Http\Controllers\Admin;

use Agentic\Admin\Services\ExecutionAdminService;
use Illuminate\Contracts\View\View;

final class ExecutionController
{
    public function __construct(
        private ExecutionAdminService $executions,
    ) {}

    public function index(): View
    {
        return view('agentic::admin.executions.index', [
            'executions' => $this->executions->list(),
        ]);
    }

    public function show(string $id): View
    {
        $execution = $this->executions->find($id);

        abort_if($execution === null, 404);

        return view('agentic::admin.executions.show', [
            'execution' => $execution,
        ]);
    }
}
