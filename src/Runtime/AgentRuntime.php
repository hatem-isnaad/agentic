<?php

namespace Agentic\Runtime;

use Agentic\Agent\AgentDefinition;
use Agentic\Context\ContextBuilder;
use Agentic\Exceptions\AgentExecutionFailedException;
use Agentic\Execution\AgentExecutionContext;
use Agentic\Execution\AgentExecutionResult;
use Agentic\Execution\ExecutionManager;
use Agentic\Execution\ExecutionStatus;
use Agentic\Integrations\LaravelAi\LaravelAiSdkAdapter;
use Agentic\Tool\Contracts\ToolContract;
use Agentic\Tool\Registry\ToolRegistry;
use Throwable;

/**
 * Orchestrates Agent execution.
 *
 * Runtime coordinates context, tools, permissions, and execution tracking,
 * then delegates AI execution to the Laravel AI SDK adapter.
 * It does not query Eloquent.
 */
final class AgentRuntime
{
    public function __construct(
        private ContextBuilder $contextBuilder,
        private ToolRegistry $tools,
        private LaravelAiSdkAdapter $ai,
        private ExecutionManager $executions,
    ) {}

    public function run(AgentDefinition $agent, AgentExecutionContext $context): AgentExecutionResult
    {
        $execution = $this->executions->start(
            agent: $agent->identifier(),
            input: [
                'message' => $context->message,
                'metadata' => $context->metadata,
                'variables' => $context->variables,
            ],
            metadata: [
                'model' => $agent->model,
                'provider' => $agent->provider,
            ],
        );

        try {
            $this->executions->addStep($execution, 'agent_start', [
                'agent' => $agent->identifier(),
            ]);

            $built = $this->contextBuilder->build($agent);
            $selectedTools = $this->resolveTools($built['tools']);

            $this->executions->addStep($execution, 'llm_request', [
                'message' => $context->message,
                'tools' => array_map(fn (ToolContract $tool) => $tool->definition()->name, $selectedTools),
            ]);

            $response = $this->ai->prompt($agent, $built, $selectedTools, $context);

            $output = [
                'text' => $response->text,
                'invocation_id' => $response->invocationId,
                'conversation_id' => $response->conversationId,
                'usage' => $response->usage,
                'execution_id' => $execution->id,
            ];

            $this->executions->addStep($execution, 'final_response', output: [
                'text' => $response->text,
            ]);

            $execution->conversationId = $response->conversationId;
            $this->executions->complete($execution, $output);

            return AgentExecutionResult::success($output);
        } catch (Throwable $exception) {
            $message = (new AgentExecutionFailedException(
                $agent->identifier(),
                $exception->getMessage(),
                $exception,
            ))->getMessage();

            $this->executions->addStep(
                $execution,
                'error',
                input: [],
                output: ['error' => $message],
                status: ExecutionStatus::Failed,
            );
            $this->executions->fail($execution, $message);

            return AgentExecutionResult::failure($message);
        }
    }

    /**
     * @param  list<string>  $names
     * @return list<ToolContract>
     */
    private function resolveTools(array $names): array
    {
        $resolved = [];

        foreach ($names as $name) {
            if ($this->tools->has($name)) {
                $resolved[] = $this->tools->resolve($name);
            }
        }

        return $resolved;
    }
}
