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

final class WorkflowBranchStepRunner
{
    public function __construct(
        private ToolFactory $tools,
        private ToolRegistry $registry,
        private ToolExecutor $executor,
        private AgentResolver $agents,
        private AgentRuntime $runtime,
    ) {}

    /**
     * @param  array<string, mixed>  $step
     * @param  array<string, mixed>  $variables
     * @return array<string, mixed>
     */
    public function run(array $step, array $variables): array
    {
        $type = strtolower((string) ($step['type'] ?? ''));

        match ($type) {
            'set' => $this->applySet($step, $variables),
            'tool' => $this->applyTool($step, $variables),
            'agent' => $this->applyAgent($step, $variables),
            default => throw new WorkflowExecutionException("Parallel branch step type [{$type}] is not supported."),
        };

        return $variables;
    }

    /**
     * @param  array<string, mixed>  $step
     * @param  array<string, mixed>  $variables
     */
    public function applySet(array $step, array &$variables): void
    {
        $payload = $step['variables'] ?? [];

        if (! is_array($payload)) {
            throw new WorkflowExecutionException('Set step requires a [variables] object.');
        }

        $variables = array_merge($variables, TemplateInterpolator::array($payload, $variables));
    }

    /**
     * @param  array<string, mixed>  $step
     * @param  array<string, mixed>  $variables
     */
    public function applyTool(array $step, array &$variables): void
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

        $result = $this->executor->execute($this->registry->resolve($toolName), new ToolExecutionContext(
            arguments: $arguments,
            runtime: new RuntimeContext($variables),
        ));

        if (! $result->success) {
            throw new WorkflowExecutionException($result->error ?? "Tool [{$toolName}] failed.");
        }

        $saveAs = (string) ($step['save_as'] ?? '');

        if ($saveAs !== '') {
            $variables[$saveAs] = $result->data;
        }
    }

    /**
     * @param  array<string, mixed>  $step
     * @param  array<string, mixed>  $variables
     */
    public function applyAgent(array $step, array &$variables): void
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
    }
}
