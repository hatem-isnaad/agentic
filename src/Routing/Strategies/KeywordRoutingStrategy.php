<?php

namespace Agentic\Routing\Strategies;

use Agentic\Routing\Contracts\RoutingStrategy;
use Agentic\Routing\RoutingContext;
use Agentic\Routing\RoutingResult;

/**
 * Routes by matching keywords in the request message.
 *
 * @example ['support' => ['refund', 'help'], 'sales' => ['pricing', 'quote']]
 */
final class KeywordRoutingStrategy implements RoutingStrategy
{
    /**
     * @param  array<string, list<string>>  $map  agentSlug => keywords
     */
    public function __construct(
        private array $map = [],
    ) {}

    public function name(): string
    {
        return 'keyword';
    }

    public function route(RoutingContext $context): ?RoutingResult
    {
        $message = strtolower($context->message);

        if ($message === '') {
            return null;
        }

        $bestAgent = null;
        $bestScore = 0;
        $matched = [];

        foreach ($this->map as $agent => $keywords) {
            $hits = [];

            foreach ($keywords as $keyword) {
                if ($keyword !== '' && str_contains($message, strtolower($keyword))) {
                    $hits[] = $keyword;
                }
            }

            $score = count($hits);

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestAgent = $agent;
                $matched = $hits;
            }
        }

        if ($bestAgent === null || $bestScore === 0) {
            return null;
        }

        return new RoutingResult(
            agent: $bestAgent,
            strategy: $this->name(),
            confidence: min(1.0, $bestScore / 3),
            metadata: ['matched' => $matched],
        );
    }
}
