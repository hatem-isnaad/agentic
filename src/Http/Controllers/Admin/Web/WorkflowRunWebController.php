<?php

namespace Agentic\Http\Controllers\Admin\Web;

use Agentic\Workflow\WorkflowExecutionService;
use Illuminate\Contracts\View\View;

final class WorkflowRunWebController
{
    public function __construct(
        private WorkflowExecutionService $workflows,
    ) {}

    public function index(): View
    {
        return view('agentic::admin.workflow-runs.index', [
            'runs' => $this->workflows->listRuns(null, null, 100, 0),
        ]);
    }

    public function show(string $uuid): View
    {
        $run = $this->workflows->showRun($uuid);

        abort_if($run === null, 404);

        return view('agentic::admin.workflow-runs.show', [
            'run' => $run,
        ]);
    }
}
