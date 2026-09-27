<?php

namespace Agentic\Workflow;

final readonly class WorkflowResult
{
    /**
     * @param  array<string, mixed>  $variables
     * @param  list<array<string, mixed>>  $trace
     */
    private function __construct(
        public bool $success,
        public array $variables,
        public array $trace,
        public ?array $output = null,
        public ?string $error = null,
        public bool $pending = false,
        public ?string $approvalId = null,
    ) {}

    /**
     * @param  array<string, mixed>  $output
     * @param  list<array<string, mixed>>  $trace
     */
    public static function completed(array $variables, array $output, array $trace): self
    {
        return new self(true, $variables, $trace, $output);
    }

    /**
     * @param  list<array<string, mixed>>  $trace
     */
    public static function failed(string $error, array $variables, array $trace): self
    {
        return new self(false, $variables, $trace, null, $error);
    }

    /**
     * @param  array<string, mixed>  $output
     * @param  list<array<string, mixed>>  $trace
     */
    public static function pending(string $approvalId, array $variables, array $trace, array $output): self
    {
        return new self(false, $variables, $trace, $output, null, true, $approvalId);
    }
}
