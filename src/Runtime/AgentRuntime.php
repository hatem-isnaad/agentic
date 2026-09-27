<?php

namespace Agentic\Runtime;

use Agentic\Agent\AgentDefinition;
use Agentic\Context\ContextBuilder;
use Agentic\Exceptions\AgentExecutionFailedException;
use Agentic\Execution\AgentExecutionContext;
use Agentic\Execution\AgentExecutionResult;
use Agentic\Integrations\LaravelAi\LaravelAiSdkAdapter;
use Agentic\Tool\Contracts\ToolContract;
use Agentic\Tool\Registry\ToolRegistry;
use Throwable;

/**
 * Orchestrates Agent execution.
 *
 * Runtime coordinates context, tools, and permissions, then delegates
 * AI execution to the Laravel AI SDK adapter. It does not query Eloquent.
 */
final class AgentRuntime
{
    public function __construct(
        private ContextBuilder $contextBuilder,
        private ToolRegistry $tools,
        private LaravelAiSdkAdapter $ai,
    ) {}

    public function run(AgentDefinition $agent, AgentExecutionContext $context): AgentExecutionResult
    {
        try {
            $built = $this->contextBuilder->build($agent);
            $selectedTools = $this->resolveTools($built['tools']);

            $response = $this->ai->prompt($agent, $built, $selectedTools, $context);

            return AgentExecutionResult::success([
                'text' => $response->text,
                'invocation_id' => $response->invocationId,
                'conversation_id' => $response->conversationId,
                'usage' => $response->usage,
            ]);
        } catch (Throwable $exception) {
            return AgentExecutionResult::failure(
                (new AgentExecutionFailedException($agent->identifier(), $exception->getMessage(), $exception))->getMessage()
            );
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
