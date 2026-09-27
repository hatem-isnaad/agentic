<?php

namespace Agentic\Routing\Strategies;

use Agentic\Routing\Contracts\RoutingStrategy;
use Agentic\Routing\RoutingContext;
use Agentic\Routing\RoutingResult;
use Closure;

final class CallbackRoutingStrategy implements RoutingStrategy
{
    /**
     * @param  Closure(RoutingContext): (RoutingResult|string|null)  $callback
     */
    public function __construct(
        private Closure $callback,
        private string $strategyName = 'callback',
    ) {}

    public function name(): string
    {
        return $this->strategyName;
    }

    public function route(RoutingContext $context): ?RoutingResult
    {
        $result = ($this->callback)($context);

        if ($result instanceof RoutingResult) {
            return $result;
        }

        if (is_string($result) && $result !== '') {
            return new RoutingResult(
                agent: $result,
                strategy: $this->name(),
            );
        }

        return null;
    }
}
