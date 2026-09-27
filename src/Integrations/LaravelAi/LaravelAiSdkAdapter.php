<?php

namespace Agentic\Integrations\LaravelAi;

use Agentic\Agent\AgentDefinition;
use Agentic\Execution\AgentExecutionContext;
use Agentic\Tool\Contracts\ToolContract;
use Agentic\Tool\ToolApprovalService;
use Agentic\Tool\ToolExecutionContext;
use Agentic\Tool\ToolExecutor;
use Laravel\Ai\Contracts\Agent as LaravelAgent;
use Laravel\Ai\Responses\AgentResponse;

use function Laravel\Ai\agent;

final class LaravelAiSdkAdapter
{
    public function __construct(
        private ToolExecutor $executor,
        private ToolApprovalService $approvals,
        private LaravelAiToolSetBuilder $toolSets,
    ) {}

    public function prompt(
        AgentDefinition $agent,
        array $builtContext,
        array $tools,
        AgentExecutionContext $context,
    ): AgentResponse {
        $sdkAgent = $this->makeAgent($agent, $builtContext, $tools, $context);

        $provider = AiProviderResolver::provider($agent->provider);
        $model = AiProviderResolver::model($agent->model);
        AiProviderResolver::assertReady($provider);

        return $sdkAgent->prompt(
            $context->message,
            provider: $provider,
            model: $model,
        );
    }

    public function makeAgent(
        AgentDefinition $agent,
        array $builtContext,
        array $tools,
        AgentExecutionContext $context,
    ): LaravelAgent {
        $baseToolContext = new ToolExecutionContext(
            arguments: [],
            metadata: $context->metadata,
            execution: $context,
            runtime: $context->runtime()->with('agent', $agent),
        );

        $laravelTools = array_map(
            fn (ToolContract $tool) => new AgenticLaravelTool(
                $tool,
                $this->executor,
                $this->approvals,
                $baseToolContext,
            ),
            $tools,
        );

        $provider = AiProviderResolver::provider($agent->provider);
        $laravelTools = $this->toolSets->build($laravelTools, $provider);

        return agent(
            instructions: $this->composeInstructions($agent, $builtContext),
            messages: $context->messages,
            tools: $laravelTools,
        );
    }

    private function composeInstructions(AgentDefinition $agent, array $builtContext): string
    {
        $parts = [
            $builtContext['instructions'] !== ''
                ? $builtContext['instructions']
                : $agent->instructions,
        ];

        if ($builtContext['skills'] !== []) {
            $skillLines = array_map(
                fn (array $skill): string => "- {$skill['name']}: {$skill['description']} (tools: ".(
                    $skill['tools'] === [] ? 'none' : implode(', ', $skill['tools'])
                ).')',
                $builtContext['skills'],
            );

            $parts[] = "Available skills:\n".implode("\n", $skillLines);
        }

        if ($builtContext['knowledge'] !== []) {
            $parts[] = "Knowledge:\n".json_encode($builtContext['knowledge'], JSON_THROW_ON_ERROR);
        }

        if (($builtContext['memory'] ?? []) !== []) {
            $parts[] = "Memory:\n".json_encode($builtContext['memory'], JSON_THROW_ON_ERROR);
        }

        return implode("\n\n", array_filter($parts, fn (string $part) => $part !== ''));
    }
}
