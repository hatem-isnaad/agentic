<?php

namespace Agentic\Execution;

final readonly class AgentExecutionResult
{
    private function __construct(
        public bool $success,
        public mixed $output = null,
        public ?string $error = null,
    ) {}

    public static function success(mixed $output = null): self
    {
        return new self(true, $output);
    }

    public static function failure(string $error): self
    {
        return new self(false, null, $error);
    }
}
