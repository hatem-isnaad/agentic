<?php

namespace Agentic\Routing\Strategies;

use Agentic\Contracts\Repositories\AgentRepository;
use Agentic\Routing\Contracts\RoutingStrategy;
use Agentic\Routing\RoutingContext;
use Agentic\Routing\RoutingResult;

/**
 * Routes using an explicit agent slug hint.
 */
final class SlugRoutingStrategy implements RoutingStrategy
{
    public function __construct(
        private ?AgentRepository $agents = null,
    ) {}

    public function name(): string
    {
        return 'slug';
    }

    public function route(RoutingContext $context): ?RoutingResult
    {
        $slug = $context->agentHint;

        if (! is_string($slug) || $slug === '') {
            return null;
        }

        if ($this->agents !== null && $this->agents->findBySlug($slug) === null) {
            return null;
        }

        return new RoutingResult(
            agent: $slug,
            strategy: $this->name(),
            confidence: 1.0,
        );
    }
}
