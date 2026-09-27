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

    public function text(): ?string
    {
        if (is_string($this->output)) {
            return $this->output;
        }

        if (is_array($this->output) && isset($this->output['text']) && is_string($this->output['text'])) {
            return $this->output['text'];
        }

        return null;
    }
}
