<?php

namespace Agentic\Tool;

final readonly class ToolDefinition
{
    public function __construct(
        public string $name,
        public string $description,
        public array $inputSchema = [],
    ) {}
}
