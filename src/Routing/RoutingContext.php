<?php

namespace Agentic\Routing;

/**
 * Input available to routing strategies. Routing never executes an Agent.
 */
final readonly class RoutingContext
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __construct(
        public string $message = '',
        public ?string $agentHint = null,
        public string|int|null $userId = null,
        public array $attributes = [],
    ) {}

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }
}
