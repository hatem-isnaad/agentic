<?php

namespace Agentic\Workflow;

use Agentic\Exceptions\WorkflowExecutionException;
use Agentic\Tool\Support\TemplateInterpolator;
use Agentic\Tool\ToolApprovalService;
use Illuminate\Support\Facades\Concurrency;
use Throwable;

final class WorkflowRunner
{
    public function __construct(
        private ToolApprovalService $approvals,
        private WorkflowBranchStepRunner $branches,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function run(
        WorkflowDefinition $workflow,
        array $input = [],
        ?WorkflowContinuation $continuation = null,
    ): WorkflowResult {
        $steps = $workflow->steps;

        if ($steps === []) {
            return WorkflowResult::failed('Workflow has no steps.', [], []);
        }

        if ($continuation !== null) {
            $variables = $continuation->variables;
            $trace = $continuation->trace;
            $pointer = $continuation->pointer;
        } else {
            $variables = ['input' => $input];
            $trace = [];
            $pointer = 0;
        }

        $indexes = $this->indexSteps($steps);
        $maxSteps = max(1, (int) config('agentic.workflows.max_steps', 100));
        $iterations = 0;

        while ($pointer !== null && $iterations < $maxSteps) {
            $iterations++;
            $step = $steps[$pointer] ?? null;

            if (! is_array($step)) {
                return WorkflowResult::failed('Invalid workflow step.', $variables, $trace);
            }

            $stepId = (string) ($step['id'] ?? 'step_'.$pointer);
            $type = strtolower((string) ($step['type'] ?? ''));
            $started = microtime(true);

            try {
                $outcome = match ($type) {
                    'set' => $this->runSet($step, $variables, $pointer, $steps, $indexes),
                    'tool' => $this->runTool($step, $variables, $pointer, $steps, $indexes),
                    'agent' => $this->runAgent($workflow, $step, $variables, $pointer, $steps, $indexes),
                    'condition' => $this->runCondition($step, $variables, $pointer, $steps, $indexes),
                    'approval' => $this->runApproval($workflow, $step, $variables, $pointer, $steps, $indexes, $trace, $started),
                    'parallel' => $this->runParallel($workflow, $step, $variables, $pointer, $steps, $indexes),
                    'complete' => $this->runComplete($step, $variables, $trace, $started),

                    default => throw new WorkflowExecutionException("Unsupported workflow step type [{$type}]."),
                };

                if ($outcome instanceof WorkflowResult) {
                    return $outcome;
                }

                $trace[] = [
                    'id' => $stepId,
                    'type' => $type,
                    'duration_ms' => (int) round((microtime(true) - $started) * 1000),
                    'next' => $steps[$outcome]['id'] ?? $outcome,
                ];

                $pointer = $outcome;
            } catch (WorkflowExecutionException $exception) {
                $trace[] = [
                    'id' => $stepId,
                    'type' => $type,
                    'error' => $exception->getMessage(),
                ];

                return WorkflowResult::failed($exception->getMessage(), $variables, $trace);
            }
        }

        if ($iterations >= $maxSteps) {
            return WorkflowResult::failed('Workflow exceeded the maximum step limit.', $variables, $trace);
        }

        return WorkflowResult::failed('Workflow ended without a complete step.', $variables, $trace);
    }

    /**
     * @param  list<array<string, mixed>>  $steps
     * @return array<string, int>
     */
    private function indexSteps(array $steps): array
    {
        $indexes = [];

        foreach ($steps as $index => $step) {
            if (! is_array($step)) {
                continue;
            }

            $indexes[(string) ($step['id'] ?? 'step_'.$index)] = $index;
        }

        return $indexes;
    }

    /**
     * @param  array<string, mixed>  $step
     * @param  array<string, mixed>  $variables
     * @param  list<array<string, mixed>>  $steps
     * @param  array<string, int>  $indexes
     */
    private function runSet(array $step, array &$variables, int $pointer, array $steps, array $indexes): int
    {
        $this->branches->applySet($step, $variables);

        return $this->resolveNext($step, $pointer, $steps, $indexes);
    }

    /**
     * @param  array<string, mixed>  $step
     * @param  array<string, mixed>  $variables
     * @param  list<array<string, mixed>>  $steps
     * @param  array<string, int>  $indexes
     */
    private function runTool(array $step, array &$variables, int $pointer, array $steps, array $indexes): int
    {
        $this->branches->applyTool($step, $variables);

        return $this->resolveNext($step, $pointer, $steps, $indexes);
    }

    /**
     * @param  array<string, mixed>  $step
     * @param  array<string, mixed>  $variables
     * @param  list<array<string, mixed>>  $steps
     * @param  array<string, int>  $indexes
     */
    private function runAgent(WorkflowDefinition $workflow, array $step, array &$variables, int $pointer, array $steps, array $indexes): int
    {
        unset($workflow);

        $this->branches->applyAgent($step, $variables);

        return $this->resolveNext($step, $pointer, $steps, $indexes);
    }

    /**
     * @param  array<string, mixed>  $step
     * @param  array<string, mixed>  $variables
     * @param  list<array<string, mixed>>  $steps
     * @param  array<string, int>  $indexes
     */
    private function runParallel(
        WorkflowDefinition $workflow,
        array $step,
        array &$variables,
        int $pointer,
        array $steps,
        array $indexes,
    ): int {
        $branches = $step['branches'] ?? [];

        if (! is_array($branches) || $branches === []) {
            throw new WorkflowExecutionException('Parallel step requires a [branches] array.');
        }

        $maxBranches = max(1, (int) config('agentic.workflows.max_parallel_branches', 10));

        if (count($branches) > $maxBranches) {
            throw new WorkflowExecutionException('Workflow exceeded the maximum parallel branch limit.');
        }

        $prepared = [];

        foreach ($branches as $branch) {
            if (! is_array($branch)) {
                continue;
            }

            $prepared[] = [
                'step' => is_array($branch['step'] ?? null) ? $branch['step'] : $branch,
                'save_as' => (string) ($branch['save_as'] ?? ''),
                'variables' => $variables,
            ];
        }

        foreach ($this->runParallelBranches($prepared) as $row) {
            if ($row['save_as'] !== '') {
                $variables[$row['save_as']] = $row['variables'];
            }
        }

        return $this->resolveNext($step, $pointer, $steps, $indexes);
    }

    /**
     * @param  list<array{step: array<string, mixed>, save_as: string, variables: array<string, mixed>}>  $prepared
     * @return list<array{save_as: string, variables: array<string, mixed>}>
     */
    private function runParallelBranches(array $prepared): array
    {
        $driver = (string) config('agentic.workflows.parallel_driver', 'process');

        if ($driver !== 'sync' && count($prepared) > 1) {
            try {
                $tasks = [];
                foreach ($prepared as $item) {
                    $tasks[] = static function () use ($item): array {
                        return [
                            'save_as' => $item['save_as'],
                            'variables' => app(WorkflowBranchStepRunner::class)->run($item['step'], $item['variables']),
                        ];
                    };
                }

                /** @var list<array{save_as: string, variables: array<string, mixed>}> $results */
                $results = Concurrency::driver($driver)->run($tasks);

                return $results;
            } catch (Throwable) {
                // Fall back to isolated sequential branches (tests / hosts without pcntl).
            }
        }

        $results = [];

        foreach ($prepared as $item) {
            $results[] = [
                'save_as' => $item['save_as'],
                'variables' => $this->branches->run($item['step'], $item['variables']),
            ];
        }

        return $results;
    }

    /**
     * @param  array<string, mixed>  $step
     * @param  array<string, mixed>  $variables
     * @param  list<array<string, mixed>>  $steps
     * @param  array<string, int>  $indexes
     * @param  list<array<string, mixed>>  $trace
     */
    private function runApproval(
        WorkflowDefinition $workflow,
        array $step,
        array &$variables,
        int $pointer,
        array $steps,
        array $indexes,
        array &$trace,
        float $started,
    ): int|WorkflowResult {
        $stepId = (string) ($step['id'] ?? 'step_'.$pointer);
        $toolName = 'workflow:'.$workflow->slug.':'.$stepId;
        $resumeKey = (string) ($step['resume_key'] ?? '_resume_approval_id');
        $resumeId = data_get($variables, 'input.'.$resumeKey);

        if (is_string($resumeId) && $resumeId !== '') {
            $approval = $this->approvals->find($resumeId);

            if ($approval === null || $approval->tool !== $toolName) {
                throw new WorkflowExecutionException('Invalid workflow approval reference.');
            }

            if ($approval->status === 'rejected') {
                throw new WorkflowExecutionException('Workflow approval was rejected.');
            }

            if ($approval->status === 'approved') {
                $saveAs = (string) ($step['save_as'] ?? '');

                if ($saveAs !== '') {
                    $variables[$saveAs] = is_array($approval->arguments) ? $approval->arguments : [];
                }

                return $this->resolveNext($step, $pointer, $steps, $indexes);
            }

            $trace[] = [
                'id' => $stepId,
                'type' => 'approval',
                'duration_ms' => (int) round((microtime(true) - $started) * 1000),
                'approval_id' => $resumeId,
                'status' => 'pending',
            ];

            return WorkflowResult::pending($resumeId, $variables, $trace, [
                'approval_id' => $resumeId,
                'resume_key' => $resumeKey,
                'variables' => $variables,
            ], $pointer);
        }

        $payload = is_array($step['payload'] ?? null)
            ? TemplateInterpolator::array($step['payload'], $variables)
            : [];

        $title = TemplateInterpolator::string((string) ($step['title'] ?? 'Workflow approval'), $variables);
        $message = TemplateInterpolator::string((string) ($step['message'] ?? ''), $variables);

        $approval = $this->approvals->createWorkflowPending(
            workflowSlug: $workflow->slug,
            stepId: $stepId,
            payload: $payload,
            title: $title,
            message: $message !== '' ? $message : null,
        );

        $trace[] = [
            'id' => $stepId,
            'type' => 'approval',
            'duration_ms' => (int) round((microtime(true) - $started) * 1000),
            'approval_id' => $approval->uuid,
            'status' => 'pending',
        ];

        return WorkflowResult::pending($approval->uuid, $variables, $trace, [
            'approval_id' => $approval->uuid,
            'title' => $title,
            'message' => $message,
            'resume_key' => $resumeKey,
            'variables' => $variables,
        ], $pointer);
    }

    /**
     * @param  array<string, mixed>  $step
     * @param  array<string, mixed>  $variables
     * @param  list<array<string, mixed>>  $steps
     * @param  array<string, int>  $indexes
     */
    private function runCondition(array $step, array $variables, int $pointer, array $steps, array $indexes): int
    {
        $when = is_array($step['when'] ?? null) ? $step['when'] : [];
        $matches = $this->matchesCondition($when, $variables);
        $target = $matches
            ? (string) ($step['goto'] ?? '')
            : (string) ($step['else'] ?? '');

        if ($target === '') {
            return $this->resolveNext($step, $pointer, $steps, $indexes);
        }

        if (! isset($indexes[$target])) {
            throw new WorkflowExecutionException("Condition references unknown step [{$target}].");
        }

        return $indexes[$target];
    }

    /**
     * @param  array<string, mixed>  $when
     * @param  array<string, mixed>  $variables
     */
    private function matchesCondition(array $when, array $variables): bool
    {
        if ($when === []) {
            return false;
        }

        $path = (string) ($when['var'] ?? '');
        $value = $path !== '' ? data_get($variables, $path) : null;

        if (array_key_exists('equals', $when)) {
            return $value == $when['equals'];
        }

        if (($when['truthy'] ?? false) === true) {
            return (bool) $value;
        }

        if (($when['empty'] ?? false) === true) {
            return $value === null || $value === '' || $value === [];
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $step
     * @param  array<string, mixed>  $variables
     * @param  list<array<string, mixed>>  $trace
     */
    private function runComplete(array $step, array $variables, array $trace, float $started): WorkflowResult
    {
        $output = is_array($step['output'] ?? null) ? $step['output'] : [];
        $output = TemplateInterpolator::array($output, $variables);

        $trace[] = [
            'id' => (string) ($step['id'] ?? 'complete'),
            'type' => 'complete',
            'duration_ms' => (int) round((microtime(true) - $started) * 1000),
        ];

        return WorkflowResult::completed($variables, $output, $trace);
    }

    /**
     * @param  array<string, mixed>  $step
     * @param  list<array<string, mixed>>  $steps
     * @param  array<string, int>  $indexes
     */
    private function resolveNext(array $step, int $pointer, array $steps, array $indexes): int
    {
        $next = (string) ($step['next'] ?? '');

        if ($next !== '') {
            if (! isset($indexes[$next])) {
                throw new WorkflowExecutionException("Step references unknown next step [{$next}].");
            }

            return $indexes[$next];
        }

        $candidate = $pointer + 1;

        if (! isset($steps[$candidate])) {
            throw new WorkflowExecutionException('Workflow has no next step after ['.($step['id'] ?? $pointer).'].');
        }

        return $candidate;
    }
}
