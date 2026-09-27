<?php

namespace Agentic\Admin\Services;

use Agentic\Admin\DTO\ExecutionData;
use Agentic\Contracts\Repositories\ExecutionRepository;

final class ExecutionAdminService
{
    public function __construct(
        private ExecutionRepository $executions,
    ) {}

    /**
     * @return list<ExecutionData>
     */
    public function list(int $limit = 50): array
    {
        return array_map(
            fn ($execution) => ExecutionData::fromExecution($execution),
            $this->executions->recent($limit),
        );
    }

    public function find(string $id): ?ExecutionData
    {
        $execution = $this->executions->find($id);

        return $execution ? ExecutionData::fromExecution($execution) : null;
    }
}
