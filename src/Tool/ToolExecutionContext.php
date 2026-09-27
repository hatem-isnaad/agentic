<?php

namespace Agentic\Tool;

final readonly class ToolExecutionContext
{
    public function __construct(
        public array $arguments = [],
        public array $metadata = [],
    ) {}
}
