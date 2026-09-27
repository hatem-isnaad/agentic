<?php

namespace Agentic\Workflow;

use Agentic\Agent\AgentResolver;
use Agentic\Context\RuntimeContext;
use Agentic\Exceptions\WorkflowExecutionException;
use Agentic\Execution\AgentExecutionContext;
use Agentic\Runtime\AgentRuntime;
use Agentic\Tool\Registry\ToolRegistry;
use Agentic\Tool\Support\TemplateInterpolator;
use Agentic\Tool\ToolExecutionContext;
use Agentic\Tool\ToolExecutor;
use Agentic\Tool\ToolFactory;

final class WorkflowRunner
{
    public function __construct(
        private ToolFactory $tools,
        private ToolRegistry $registry,
        private ToolExecutor $executor,
        private AgentResolver $agents,
        private AgentRuntime $runtime,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function run(WorkflowDefinition $workflow, array $input = []): WorkflowResult
    {
        $steps = $workflow->steps;

        if ($steps === []) {
            return WorkflowResult::failed('Workflow has no steps.', [], []);
        }

        $variables = ['input' => $input];
        $trace = [];
        $indexes = $this->indexSteps($steps);
        $pointer = 0;
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
                    'agent' => $this->runAgent($step, $variables, $pointer, $steps, $indexes),
                    'condition' => $this->runCondition($step, $variables, $pointer, $steps, $indexes),
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
        $payload = $step['variables'] ?? [];

        if (! is_array($payload)) {
            throw new WorkflowExecutionException('Set step requires a [variables] object.');
        }

        $variables = array_merge($variables, TemplateInterpolator::array($payload, $variables));

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
        $toolName = (string) ($step['tool'] ?? '');

        if ($toolName === '') {
            throw new WorkflowExecutionException('Tool step requires [tool].');
        }

        if (! $this->registry->has($toolName) && ! $this->tools->ensureRegistered($toolName)) {
            throw new WorkflowExecutionException("Tool [{$toolName}] is not available.");
        }

        $arguments = is_array($step['arguments'] ?? null) ? $step['arguments'] : [];
        $arguments = TemplateInterpolator::array($arguments, $variables);

        $context = new ToolExecutionContext(
            arguments: $arguments,
            runtime: new RuntimeContext($variables),
        );

        $result = $this->executor->execute($this->registry->resolve($toolName), $context);

        if (! $result->success) {
            throw new WorkflowExecutionException($result->error ?? "Tool [{$toolName}] failed.");
        }

        $saveAs = (string) ($step['save_as'] ?? '');

        if ($saveAs !== '') {
            $variables[$saveAs] = $result->data;
        }

        return $this->resolveNext($step, $pointer, $steps, $indexes);
    }

    /**
     * @param  array<string, mixed>  $step
     * @param  array<string, mixed>  $variables
     * @param  list<array<string, mixed>>  $steps
     * @param  array<string, int>  $indexes
     */
    private function runAgent(array $step, array &$variables, int $pointer, array $steps, array $indexes): int
    {
        $agentSlug = (string) ($step['agent'] ?? '');

        if ($agentSlug === '') {
            throw new WorkflowExecutionException('Agent step requires [agent].');
        }

        $message = TemplateInterpolator::string((string) ($step['message'] ?? ''), $variables);

        if ($message === '') {
            throw new WorkflowExecutionException('Agent step requires [message].');
        }

        $agent = $this->agents->resolve($agentSlug);
        $execution = $this->runtime->run(
            $agent,
            new AgentExecutionContext(
                message: $message,
                variables: $variables,
                runtime: new RuntimeContext($variables),
            ),
        );

        if (! $execution->success) {
            throw new WorkflowExecutionException($execution->error ?? "Agent [{$agentSlug}] failed.");
        }

        $saveAs = (string) ($step['save_as'] ?? '');

        if ($saveAs !== '') {
            $variables[$saveAs] = $execution->output;
        }

        return $this->resolveNext($step, $pointer, $steps, $indexes);
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
