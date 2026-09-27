<?php

namespace Agentic\Workflow;

use Agentic\Contracts\Repositories\WorkflowRepository;
use Agentic\Exceptions\WorkflowNotFoundException;

final class WorkflowResolver
{
    public function __construct(
        private WorkflowRepository $workflows,
    ) {}

    public function resolve(string $slug): WorkflowDefinition
    {
        $workflow = $this->workflows->findBySlug($slug);

        if ($workflow === null) {
            throw new WorkflowNotFoundException($slug);
        }

        return $workflow;
    }
}
