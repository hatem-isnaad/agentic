<?php

namespace Agentic\Integrations\LaravelAi;

use Agentic\Agent\AgentDefinition;
use Agentic\Context\LlmInstructionComposer;
use Agentic\Execution\AgentExecutionContext;
use Agentic\Tool\Contracts\ToolContract;
use Agentic\Tool\ToolApprovalService;
use Agentic\Tool\ToolExecutionContext;
use Agentic\Tool\ToolExecutor;
use Laravel\Ai\Contracts\Agent as LaravelAgent;
use Laravel\Ai\Files\LocalDocument;
use Laravel\Ai\Files\LocalImage;
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Streaming\Events\TextDelta;

use function Laravel\Ai\agent;

final class LaravelAiSdkAdapter
{
    public function __construct(
        private ToolExecutor $executor,
        private ToolApprovalService $approvals,
        private LaravelAiToolSetBuilder $toolSets,
        private LlmInstructionComposer $instructions,
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

        $attachments = $this->sdkAttachments($context);
        $onDelta = $context->runtime()->get('on_text_delta');

        if (is_callable($onDelta)) {
            $final = null;
            $sdkAgent->stream($context->message, $attachments, $provider, $model)
                ->each(function ($event) use ($onDelta): void {
                    if ($event instanceof TextDelta && $event->delta !== '') {
                        $onDelta($event->delta);
                    }
                })
                ->then(function ($response) use (&$final): void {
                    $final = $response;
                });

            if ($final instanceof AgentResponse) {
                return $final;
            }

            throw new \RuntimeException('Streaming completed without a final agent response.');
        }

        return $sdkAgent->prompt(
            $context->message,
            $attachments,
            provider: $provider,
            model: $model,
        );
    }

    /**
     * @return list<LocalImage|LocalDocument>
     */
    private function sdkAttachments(AgentExecutionContext $context): array
    {
        $out = [];

        foreach ($context->attachments as $file) {
            if (! is_array($file)) {
                continue;
            }
            $absolute = storage_path('app/'.ltrim((string) ($file['path'] ?? ''), '/'));
            $mime = (string) ($file['mime'] ?? '');
            if ($absolute === '' || ! is_file($absolute)) {
                continue;
            }
            if (str_starts_with($mime, 'image/')) {
                $out[] = new LocalImage($absolute, $mime);
            } else {
                $out[] = new LocalDocument($absolute, $mime);
            }
        }

        return $out;
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
            instructions: $this->instructions->compose($builtContext, $agent->instructions),
            messages: $context->messages,
            tools: $laravelTools,
        );
    }
}
