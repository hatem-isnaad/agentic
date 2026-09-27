<?php

namespace Agentic\Tool;

final readonly class ToolResult
{
    private function __construct(
        public bool $success,
        public mixed $data = null,
        public ?string $error = null,
    ) {}

    public static function success(mixed $data = null): self
    {
        return new self(true, $data);
    }

    public static function failure(string $error): self
    {
        return new self(false, null, $error);
    }
}
