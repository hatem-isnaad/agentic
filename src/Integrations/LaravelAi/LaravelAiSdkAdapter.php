<?php

namespace Agentic\Integrations\LaravelAi;

use Agentic\Agent\AgentDefinition;
use Agentic\Execution\AgentExecutionContext;
use Agentic\Permission\PermissionChecker;
use Agentic\Tool\Contracts\ToolContract;
use Agentic\Tool\ToolExecutionContext;
use Laravel\Ai\Contracts\Agent as LaravelAgent;
use Laravel\Ai\Responses\AgentResponse;

use function Laravel\Ai\agent;

/**
 * Integration boundary between Agentic orchestration and Laravel AI SDK execution.
 *
 * Agentic owns configuration, tools, permissions, and context.
 * Laravel AI SDK owns provider communication and LLM execution.
 */
final class LaravelAiSdkAdapter
{
    public function __construct(
        private PermissionChecker $permissions,
    ) {}

    /**
     * @param  array{
     *     instructions: string,
     *     skills: list<array{name: string, description: string, tools: list<string>}>,
     *     knowledge: list<mixed>,
     *     tools?: list<string>
     *  }  $builtContext
     * @param  list<ToolContract>  $tools
     */
    public function prompt(
        AgentDefinition $agent,
        array $builtContext,
        array $tools,
        AgentExecutionContext $context,
    ): AgentResponse {
        $sdkAgent = $this->makeAgent($agent, $builtContext, $tools, $context);

        return $sdkAgent->prompt(
            $context->message,
            provider: $agent->provider ?? config('agentic.ai.provider'),
            model: $agent->model ?? config('agentic.ai.model'),
        );
    }

    /**
     * @param  array{
     *     instructions: string,
     *     skills: list<array{name: string, description: string, tools: list<string>}>,
     *     knowledge: list<mixed>,
     *     tools?: list<string>
     *  }  $builtContext
     * @param  list<ToolContract>  $tools
     */
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
            fn (ToolContract $tool) => new AgenticLaravelTool($tool, $this->permissions, $baseToolContext),
            $tools,
        );

        return agent(
            instructions: $this->composeInstructions($agent, $builtContext),
            messages: $context->messages,
            tools: $laravelTools,
        );
    }

    /**
     * @param  array{
     *     instructions: string,
     *     skills: list<array{name: string, description: string, tools: list<string>}>,
     *     knowledge: list<mixed>
     *  }  $builtContext
     */
    private function composeInstructions(AgentDefinition $agent, array $builtContext): string
    {
        $parts = [
            $builtContext['instructions'] !== ''
                ? $builtContext['instructions']
                : $agent->instructions,
        ];

        if ($builtContext['skills'] !== []) {
            $skillLines = array_map(
                function (array $skill): string {
                    $tools = $skill['tools'] === []
                        ? 'none'
                        : implode(', ', $skill['tools']);

                    return "- {$skill['name']}: {$skill['description']} (tools: {$tools})";
                },
                $builtContext['skills'],
            );

            $parts[] = "Available skills:\n".implode("\n", $skillLines);
        }

        if ($builtContext['knowledge'] !== []) {
            $parts[] = "Knowledge:\n".json_encode($builtContext['knowledge'], JSON_THROW_ON_ERROR);
        }

        return implode("\n\n", array_filter($parts, fn (string $part) => $part !== ''));
    }
}
