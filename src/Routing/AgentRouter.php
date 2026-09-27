<?php

namespace Agentic\Routing;

use Agentic\Exceptions\AgentNotFoundException;
use Agentic\Routing\Contracts\RoutingStrategy;

/**
 * Resolves which Agent should handle a request.
 *
 * Does not execute the Agent — callers pass the result to AgentResolver / AgentRuntime.
 */
final class AgentRouter
{
    /** @var list<RoutingStrategy> */
    private array $strategies = [];

    public function __construct(
        private ?string $fallbackAgent = null,
    ) {}

    public function use(RoutingStrategy $strategy): self
    {
        $this->strategies[] = $strategy;

        return $this;
    }

    /**
     * @param  list<RoutingStrategy>  $strategies
     */
    public function strategies(array $strategies): self
    {
        foreach ($strategies as $strategy) {
            $this->use($strategy);
        }

        return $this;
    }

    public function route(RoutingContext $context): RoutingResult
    {
        foreach ($this->strategies as $strategy) {
            $result = $strategy->route($context);

            if ($result !== null) {
                return $result;
            }
        }

        if ($this->fallbackAgent !== null) {
            return new RoutingResult(
                agent: $this->fallbackAgent,
                strategy: 'fallback',
                confidence: 0.0,
            );
        }

        throw new AgentNotFoundException('route:'.($context->agentHint ?? 'unknown'));
    }
}
