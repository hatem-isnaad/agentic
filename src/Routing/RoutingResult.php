<?php

namespace Agentic\Routing;

/**
 * Result of routing a request to an Agent identifier.
 */
final readonly class RoutingResult
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $agent,
        public string $strategy,
        public float $confidence = 1.0,
        public array $metadata = [],
    ) {}
}
