<?php

namespace Agentic\Routing\Strategies;

use Agentic\Routing\Contracts\RoutingStrategy;
use Agentic\Routing\RoutingContext;
use Agentic\Routing\RoutingResult;
use Agentic\Routing\Support\AgentLlmSelector;

/**
 * Optional LLM agent routing — register after slug/keyword/callback strategies.
 */
final class LlmAgentRoutingStrategy implements RoutingStrategy
{
    /**
     * @param  list<array{slug: string, label: string, description?: string}>  $catalog
     */
    public function __construct(
        private AgentLlmSelector $selector,
        private array $catalog = [],
        private ?string $provider = null,
        private ?string $model = null,
    ) {}

    public function name(): string
    {
        return 'llm';
    }

    public function route(RoutingContext $context): ?RoutingResult
    {
        $catalog = $this->catalog;

        if ($catalog === []) {
            $catalog = $context->get('agent_catalog', []);

            if (! is_array($catalog)) {
                return null;
            }
        }

        if ($context->agentHint !== null && $context->agentHint !== '') {
            return null;
        }

        $slug = $this->selector->select(
            $context->message,
            $catalog,
            $this->provider,
            $this->model,
        );

        if ($slug === null) {
            return null;
        }

        return new RoutingResult(
            agent: $slug,
            strategy: $this->name(),
            confidence: 0.75,
            metadata: ['model' => $this->model ?? config('agentic.ai.model')],
        );
    }
}
