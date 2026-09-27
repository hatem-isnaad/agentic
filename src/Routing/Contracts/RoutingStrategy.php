<?php

namespace Agentic\Routing\Contracts;

use Agentic\Routing\RoutingContext;
use Agentic\Routing\RoutingResult;

interface RoutingStrategy
{
    public function name(): string;

    public function route(RoutingContext $context): ?RoutingResult;
}
